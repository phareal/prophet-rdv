<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Statut;
use ProphetCore\Paiement\Webhook;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class WebhookTest extends TestCase
{
    private const SECRET = 'secret_webhook';

    private array $metas = [];

    private function contexte(?int $postId = 7): void
    {
        $this->metas = [];

        Functions\when('get_post_meta')->alias(fn ($id, $cle, $s) => $this->metas[$cle] ?? '');
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) {
            $this->metas[$cle] = $v;

            return true;
        });
        Functions\when('get_posts')->justReturn($postId === null ? [] : [$postId]);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('error_log')->justReturn(true);
        $_ENV['MONEROO_WEBHOOK_SECRET'] = self::SECRET;
    }

    private function corps(string $evenement = 'payment.success', string $statut = 'success'): string
    {
        return (string) json_encode([
            'event' => $evenement,
            'data' => ['id' => 'tx_1', 'status' => $statut, 'payment_method' => 'mtn_bj'],
        ]);
    }

    private function signer(string $corps): string
    {
        return hash_hmac('sha256', $corps, self::SECRET);
    }

    public function test_une_signature_valide_est_acceptee(): void
    {
        $corps = $this->corps();

        $this->assertTrue(Webhook::signatureValide($corps, $this->signer($corps), self::SECRET));
    }

    public function test_une_signature_invalide_est_rejetee(): void
    {
        $corps = $this->corps();

        $this->assertFalse(Webhook::signatureValide($corps, 'mauvaise', self::SECRET));
    }

    public function test_un_corps_modifie_invalide_la_signature(): void
    {
        $signature = $this->signer($this->corps());
        $corpsFalsifie = $this->corps(statut: 'success') . ' ';

        $this->assertFalse(Webhook::signatureValide($corpsFalsifie, $signature, self::SECRET));
    }

    public function test_une_signature_absente_est_rejetee(): void
    {
        $this->assertFalse(Webhook::signatureValide($this->corps(), '', self::SECRET));
    }

    public function test_un_secret_absent_rejette_tout(): void
    {
        $corps = $this->corps();

        $this->assertFalse(Webhook::signatureValide($corps, $this->signer($corps), ''));
    }

    public function test_une_signature_invalide_renvoie_403_sans_rien_ecrire(): void
    {
        $this->contexte();
        Functions\expect('update_post_meta')->never();

        $code = (new Webhook())->traiter($this->corps(), 'mauvaise');

        $this->assertSame(403, $code);
    }

    public function test_un_paiement_reussi_met_le_rendez_vous_a_jour(): void
    {
        $this->contexte();
        $corps = $this->corps();

        $code = (new Webhook())->traiter($corps, $this->signer($corps));

        $this->assertSame(200, $code);
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_le_meme_evenement_recu_deux_fois_ne_produit_qu_un_effet(): void
    {
        $this->contexte();
        $corps = $this->corps();
        $signature = $this->signer($corps);
        $webhook = new Webhook();

        $this->assertSame(200, $webhook->traiter($corps, $signature));
        $this->assertSame(200, $webhook->traiter($corps, $signature));
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_une_transaction_inconnue_est_acquittee_sans_erreur(): void
    {
        // Acquitter évite que Moneroo réessaie indéfiniment un événement qui ne
        // nous concerne pas.
        $this->contexte(postId: null);
        $corps = $this->corps();

        $this->assertSame(200, (new Webhook())->traiter($corps, $this->signer($corps)));
    }

    public function test_un_corps_illisible_renvoie_400(): void
    {
        $this->contexte();
        $corps = 'pas du json';

        $this->assertSame(400, (new Webhook())->traiter($corps, $this->signer($corps)));
    }
}

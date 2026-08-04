<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\RetourHandler;
use ProphetCore\Paiement\Statut;
use ProphetCore\Rdv\RendezVousPayable;
use ProphetCore\Tests\TestCase;

final class RetourHandlerTest extends TestCase
{
    private array $metas = [];

    private function sujet(int $postId = 7): RendezVousPayable
    {
        return new RendezVousPayable(['post_id' => $postId]);
    }

    private function transaction(string $statutMoneroo, string $methode = 'mtn_bj'): void
    {
        $this->metas = [];

        Functions\when('get_post_meta')->alias(fn ($id, $cle, $s) => $this->metas[$cle] ?? '');
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) {
            $this->metas[$cle] = $v;

            return true;
        });
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode([
            'data' => [
                'id' => 'tx_1',
                'status' => $statutMoneroo,
                'payment_method' => $methode,
            ],
        ]));
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('error_log')->justReturn(true);
        $_ENV['MONEROO_SECRET_KEY'] = 'cle_test';
    }

    public function test_un_paiement_reussi_est_enregistre_comme_regle(): void
    {
        $this->transaction('success');

        $statut = (new RetourHandler())->verifier($this->sujet(), 'tx_1');

        $this->assertSame(Statut::PAYE, $statut);
    }

    public function test_un_paiement_echoue_est_enregistre_comme_tel(): void
    {
        $this->transaction('failed');

        $this->assertSame(Statut::ECHOUE, (new RetourHandler())->verifier($this->sujet(), 'tx_1'));
    }

    public function test_le_moyen_de_paiement_renvoye_est_conserve(): void
    {
        $this->transaction('success', 'orange_money_sn');

        (new RetourHandler())->verifier($this->sujet(), 'tx_1');

        $this->assertSame(
            'orange_money_sn',
            $this->metas[\ProphetCore\PostTypes\RendezVous::metaKey('paiement_methode')]
        );
    }

    public function test_un_appel_moneroo_en_echec_laisse_le_statut_inchange(): void
    {
        $this->transaction('success');
        Functions\when('is_wp_error')->justReturn(true);
        $this->metas[\ProphetCore\PostTypes\RendezVous::metaKey('paiement_statut')] = Statut::EN_ATTENTE;

        $statut = (new RetourHandler())->verifier($this->sujet(), 'tx_1');

        $this->assertSame(Statut::EN_ATTENTE, $statut);
    }
}

<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\DonRepository;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\Don;
use ProphetCore\Tests\TestCase;

final class DonRepositoryTest extends TestCase
{
    private array $metas = [];

    private function base(): void
    {
        $this->metas = [];

        Functions\when('wp_generate_password')->justReturn('REFDON0123456789');
        Functions\when('wp_insert_post')->justReturn(11);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) {
            $this->metas[$cle] = $v;

            return true;
        });
    }

    private function donnees(): array
    {
        return [
            'motif_id' => 3,
            'motif_titre' => 'Dîmes',
            'montant' => 2500,
            'devise' => 'XOF',
            'nom' => 'Doe', 'prenom' => 'Jane',
            'email' => 'jane@example.test', 'telephone' => '+22890000000',
            'message' => 'Que Dieu vous bénisse',
        ];
    }

    public function test_la_creation_renvoie_une_reference_et_ecrit_les_metadonnees(): void
    {
        $this->base();

        $ref = (new DonRepository())->creer($this->donnees());

        $this->assertSame('REFDON0123456789', $ref);
        $this->assertSame(3, $this->metas[Don::metaKey('motif')]);
        $this->assertSame(2500, $this->metas[Don::metaKey('montant')]);
        $this->assertSame('jane@example.test', $this->metas[Don::metaKey('email')]);
        $this->assertSame('REFDON0123456789', $this->metas[Don::metaKey('ref')]);
    }

    public function test_toutes_les_cles_portent_le_prefixe_du_type(): void
    {
        // Carbon Fields préfixe d'un underscore : une clé littérale créerait un
        // second jeu de données que l'administration n'afficherait pas.
        $this->base();

        (new DonRepository())->creer($this->donnees());

        foreach (array_keys($this->metas) as $cle) {
            $this->assertStringStartsWith('_don_', $cle);
        }
    }

    public function test_le_don_naît_en_attente_de_paiement(): void
    {
        $capture = [];
        $this->base();
        Functions\when('wp_insert_post')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return 11;
        });

        (new DonRepository())->creer($this->donnees());

        $this->assertSame(Don::SLUG, $capture['post_type']);
        $this->assertSame(Don::STATUT_EN_ATTENTE, $capture['post_status']);
        $this->assertStringContainsString('Dîmes', $capture['post_title']);
    }

    /**
     * Sans cette méta, PaiementRepository::statut() retombe sur
     * NON_REQUIS : la colonne d'administration affiche « Non requis » pour
     * un don en attente de paiement, et le filtre « En attente » le manque.
     */
    public function test_le_statut_de_paiement_nait_en_attente(): void
    {
        $this->base();

        (new DonRepository())->creer($this->donnees());

        $this->assertSame(Statut::EN_ATTENTE, $this->metas[Don::metaKey('paiement_statut')]);
    }

    public function test_une_reference_inconnue_renvoie_null(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertNull((new DonRepository())->parRef('INCONNUE'));
    }
}

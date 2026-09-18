<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\ExportCsv;
use ProphetCore\Tests\TestCase;
use RuntimeException;

final class ExportCsvTest extends TestCase
{
    private function don(): array
    {
        return [
            'date' => '2026-08-04 10:00:00',
            'motif_titre' => 'Dîmes',
            'montant' => 2500,
            'devise' => 'XOF',
            'prenom' => 'Jane', 'nom' => 'Doe',
            'email' => 'jane@example.test', 'telephone' => '+22890000000',
            'statut_paiement' => 'paye',
            'ref' => 'REF123',
        ];
    }

    public function test_l_entete_est_en_francais_et_dans_un_ordre_stable(): void
    {
        $lignes = ExportCsv::lignes([]);

        $this->assertSame(
            ['Date', 'Motif', 'Montant', 'Devise', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Statut', 'Référence'],
            $lignes[0]
        );
    }

    public function test_un_don_produit_une_ligne_dans_le_meme_ordre(): void
    {
        $lignes = ExportCsv::lignes([$this->don()]);

        $this->assertCount(2, $lignes);
        $this->assertSame('Dîmes', $lignes[1][1]);
        $this->assertSame(2500, $lignes[1][2]);
        $this->assertSame('REF123', $lignes[1][9]);
    }

    public function test_le_statut_est_traduit_en_francais(): void
    {
        $lignes = ExportCsv::lignes([$this->don()]);

        $this->assertSame('Réglé', $lignes[1][8]);
    }

    /**
     * sanitize_text_field() laisse passer =, +, -, @ : un tableur (Excel,
     * LibreOffice…) qui ouvre le CSV interprète une cellule commençant par
     * l'un de ces caractères comme une formule. Un prénom
     * =HYPERLINK("http://…"&A1) l'exécuterait chez le trésorier. Préfixer
     * d'une apostrophe neutralise l'interprétation sans changer la valeur
     * affichée.
     */
    public function test_un_prenom_qui_ressemble_a_une_formule_est_protege(): void
    {
        $lignes = ExportCsv::lignes([array_merge($this->don(), [
            'prenom' => '=HYPERLINK("http://malveillant.test","clic")',
        ])]);

        $this->assertSame(
            '\'=HYPERLINK("http://malveillant.test","clic")',
            $lignes[1][4]
        );
    }

    public function test_un_nom_commencant_par_plus_est_protege(): void
    {
        $lignes = ExportCsv::lignes([array_merge($this->don(), ['nom' => '+cmd|"/c calc"!A1'])]);

        $this->assertSame('\'+cmd|"/c calc"!A1', $lignes[1][5]);
    }

    public function test_un_motif_commencant_par_moins_est_protege(): void
    {
        $lignes = ExportCsv::lignes([array_merge($this->don(), ['motif_titre' => '-2+3'])]);

        $this->assertSame('\'-2+3', $lignes[1][1]);
    }

    public function test_une_valeur_commencant_par_arobase_est_protegee(): void
    {
        $lignes = ExportCsv::lignes([array_merge($this->don(), ['prenom' => '@SUM(1+1)'])]);

        $this->assertSame('\'@SUM(1+1)', $lignes[1][4]);
    }

    public function test_une_valeur_commencant_par_une_tabulation_est_protegee(): void
    {
        $lignes = ExportCsv::lignes([array_merge($this->don(), ['prenom' => "\t=2+2"])]);

        $this->assertSame("'\t=2+2", $lignes[1][4]);
    }

    public function test_un_prenom_ordinaire_n_est_pas_modifie(): void
    {
        $lignes = ExportCsv::lignes([$this->don()]);

        $this->assertSame('Jane', $lignes[1][4]);
    }

    /**
     * Le dump contient noms, emails et téléphones des donateurs. edit_posts
     * est accordé aux Contributeurs par défaut sur WordPress : avec cette
     * capacité, un Contributeur pouvait télécharger la liste de tous les
     * donateurs. manage_options est réservé à l'Administrateur.
     */
    public function test_l_export_exige_manage_options_et_non_edit_posts(): void
    {
        Functions\expect('current_user_can')->once()->with('manage_options')->andReturn(false);
        Functions\when('wp_die')->alias(function () {
            throw new RuntimeException('halt');
        });

        $this->expectException(RuntimeException::class);

        ExportCsv::telecharger();
    }

    /**
     * fields => 'ids' ne remonte que les identifiants (pas la ligne wp_posts
     * complète), ce qui court-circuite l'amorçage automatique du cache de
     * méta que WP_Query fait pour des objets complets : sans l'appel
     * explicite à _prime_post_caches(), chacun des sept champs lus par ligne
     * de l'export interrogerait la base séparément — invisible avec deux
     * dons, sensible à deux cents lors d'un culte.
     */
    public function test_la_recuperation_amorce_le_cache_de_meta(): void
    {
        Functions\when('get_posts')->justReturn([5]);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_the_date')->justReturn('2026-08-05 10:00:00');

        Functions\expect('_prime_post_caches')->once()->with([5], false, true);

        $dons = ExportCsv::recupererDons();

        $this->assertCount(1, $dons);
    }

    /**
     * La récupération lit bien chaque champ attendu par lignes() — pas
     * seulement qu'elle amorce le cache, mais qu'elle produit toujours les
     * bonnes données une fois passée en fields => 'ids'.
     */
    public function test_la_recuperation_produit_les_champs_attendus(): void
    {
        Functions\when('get_posts')->justReturn([5]);
        Functions\when('_prime_post_caches')->justReturn(null);
        Functions\when('get_the_date')->justReturn('2026-08-05 10:00:00');
        $metas = [
            'motif_titre' => 'Dîmes', 'montant' => '2500', 'devise' => 'XOF',
            'prenom' => 'Jane', 'nom' => 'Doe', 'email' => 'jane@example.test',
            'telephone' => '+22890000000', 'ref' => 'REF123',
        ];
        Functions\when('get_post_meta')->alias(
            static fn (int $id, string $cle) => $metas[str_replace('_don_', '', $cle)] ?? ''
        );

        $dons = ExportCsv::recupererDons();

        $this->assertCount(1, $dons);
        $this->assertSame('2026-08-05 10:00:00', $dons[0]['date']);
        $this->assertSame('Dîmes', $dons[0]['motif_titre']);
        $this->assertSame(2500, $dons[0]['montant']);
        $this->assertSame('REF123', $dons[0]['ref']);
    }
}

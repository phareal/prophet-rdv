<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use ProphetCore\Don\ExportCsv;
use ProphetCore\Tests\TestCase;

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
}

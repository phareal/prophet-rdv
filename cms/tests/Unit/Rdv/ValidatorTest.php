<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Rdv\Validator;
use ProphetCore\Tests\TestCase;

final class ValidatorTest extends TestCase
{
    private function validator(): Validator
    {
        return new Validator(
            ['08h00', '09h00', '10h00'],
            ['Mariage', 'Anges', 'Santé'],
            ['mobile_money', 'virement', 'paypal', 'crypto'],
            new DateTimeImmutable('2026-07-30 12:00:00', new DateTimeZone('UTC'))
        );
    }

    private function valide(array $surcharges = []): array
    {
        return array_merge([
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.test',
            'telephone' => '+22890000000',
            'pays' => 'Togo',
            'date' => '2026-08-05',       // mercredi, dans le futur
            'heure' => '09h00',
            'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money',
            'message' => 'Merci.',
        ], $surcharges);
    }

    public function test_une_demande_complete_est_valide_et_la_date_est_convertie(): void
    {
        $resultat = $this->validator()->validate($this->valide());

        $this->assertTrue($resultat->isValid());
        $this->assertSame([], $resultat->errors());
        $this->assertInstanceOf(DateTimeImmutable::class, $resultat->data()['date']);
        $this->assertSame('2026-08-05', $resultat->data()['date']->format('Y-m-d'));
    }

    public function test_le_nom_et_le_prenom_exigent_deux_caracteres(): void
    {
        $resultat = $this->validator()->validate($this->valide(['nom' => 'D', 'prenom' => 'J']));

        $this->assertSame(
            'Le nom doit contenir au moins 2 caractères',
            $resultat->errors()['nom']
        );
        $this->assertSame(
            'Le prénom doit contenir au moins 2 caractères',
            $resultat->errors()['prenom']
        );
    }

    public function test_l_email_doit_etre_valide(): void
    {
        $resultat = $this->validator()->validate($this->valide(['email' => 'pas-un-email']));

        $this->assertSame('Adresse email invalide', $resultat->errors()['email']);
    }

    public function test_le_telephone_exige_huit_caracteres(): void
    {
        $resultat = $this->validator()->validate($this->valide(['telephone' => '+228']));

        $this->assertSame('Numéro de téléphone invalide', $resultat->errors()['telephone']);
    }

    public function test_le_pays_est_obligatoire(): void
    {
        $resultat = $this->validator()->validate($this->valide(['pays' => '']));

        $this->assertSame(
            'Veuillez indiquer votre pays de résidence',
            $resultat->errors()['pays']
        );
    }

    public function test_le_dimanche_est_refuse(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => '2026-08-02']));

        $this->assertSame('Pas de rendez-vous le dimanche', $resultat->errors()['date']);
    }

    public function test_une_date_passee_est_refusee(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => '2026-07-20']));

        $this->assertSame('La date doit être dans le futur', $resultat->errors()['date']);
    }

    public function test_le_jour_meme_est_refuse(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => '2026-07-30']));

        $this->assertSame('La date doit être dans le futur', $resultat->errors()['date']);
    }

    public function test_une_date_illisible_est_refusee(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => 'n\'importe quoi']));

        $this->assertSame('Veuillez sélectionner une date', $resultat->errors()['date']);
    }

    public function test_l_heure_doit_faire_partie_des_creneaux_configures(): void
    {
        $resultat = $this->validator()->validate($this->valide(['heure' => '23h00']));

        $this->assertSame('Veuillez sélectionner une heure', $resultat->errors()['heure']);
    }

    public function test_le_type_de_consultation_doit_exister(): void
    {
        $resultat = $this->validator()->validate($this->valide(['type_consultation' => 'Inconnu']));

        $this->assertSame(
            'Veuillez choisir un type de consultation',
            $resultat->errors()['type_consultation']
        );
    }

    public function test_une_inscription_a_un_evenement_est_acceptee(): void
    {
        $resultat = $this->validator()->validate(
            $this->valide(['type_consultation' => 'Inscription — Retraite Spirituelle'])
        );

        $this->assertTrue($resultat->isValid());
    }

    public function test_le_mode_de_paiement_doit_faire_partie_des_modes_configures(): void
    {
        $resultat = $this->validator()->validate($this->valide(['mode_paiement' => 'especes']));

        $this->assertSame(
            'Veuillez sélectionner un mode de paiement',
            $resultat->errors()['mode_paiement']
        );
    }

    public function test_le_message_est_facultatif_mais_plafonne_a_500_caracteres(): void
    {
        $court = $this->validator()->validate($this->valide(['message' => '']));
        $long = $this->validator()->validate($this->valide(['message' => str_repeat('a', 501)]));

        $this->assertTrue($court->isValid());
        $this->assertSame(
            'Le message ne peut pas dépasser 500 caractères',
            $long->errors()['message']
        );
    }
}

<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/** Règles reprises de lib/validations.ts:38-63. */
final class Validator
{
    public const PREFIXE_EVENEMENT = 'Inscription — ';

    private DateTimeImmutable $maintenant;

    /**
     * @param array<int, string> $heures
     * @param array<int, string> $typesAutorises
     * @param array<int, string> $modesAutorises
     */
    public function __construct(
        private readonly array $heures,
        private readonly array $typesAutorises,
        private readonly array $modesAutorises,
        ?DateTimeImmutable $maintenant = null,
    ) {
        $this->maintenant = $maintenant ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function validate(array $input): ValidationResult
    {
        $errors = [];
        $data = [];

        $data['nom'] = trim((string) ($input['nom'] ?? ''));
        if (mb_strlen($data['nom']) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
        }

        $data['prenom'] = trim((string) ($input['prenom'] ?? ''));
        if (mb_strlen($data['prenom']) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        $data['email'] = trim((string) ($input['email'] ?? ''));
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Adresse email invalide';
        }

        $data['telephone'] = trim((string) ($input['telephone'] ?? ''));
        if (mb_strlen($data['telephone']) < 8) {
            $errors['telephone'] = 'Numéro de téléphone invalide';
        }

        $data['pays'] = trim((string) ($input['pays'] ?? ''));
        if ($data['pays'] === '') {
            $errors['pays'] = 'Veuillez indiquer votre pays de résidence';
        }

        $date = $this->parseDate((string) ($input['date'] ?? ''));
        if ($date === null) {
            $errors['date'] = 'Veuillez sélectionner une date';
        } elseif ((int) $date->format('w') === 0) {
            $errors['date'] = 'Pas de rendez-vous le dimanche';
        } elseif ($date <= $this->maintenant->setTime(0, 0)) {
            $errors['date'] = 'La date doit être dans le futur';
        } else {
            $data['date'] = $date;
        }

        $data['heure'] = trim((string) ($input['heure'] ?? ''));
        if (! in_array($data['heure'], $this->heures, true)) {
            $errors['heure'] = 'Veuillez sélectionner une heure';
        }

        $data['type_consultation'] = trim((string) ($input['type_consultation'] ?? ''));
        if (! $this->typeAutorise($data['type_consultation'])) {
            $errors['type_consultation'] = 'Veuillez choisir un type de consultation';
        }

        $data['mode_paiement'] = trim((string) ($input['mode_paiement'] ?? ''));
        if (! in_array($data['mode_paiement'], $this->modesAutorises, true)) {
            $errors['mode_paiement'] = 'Veuillez sélectionner un mode de paiement';
        }

        $data['message'] = trim((string) ($input['message'] ?? ''));
        if (mb_strlen($data['message']) > 500) {
            $errors['message'] = 'Le message ne peut pas dépasser 500 caractères';
        }

        return new ValidationResult($errors, $data);
    }

    private function parseDate(string $raw): ?DateTimeImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            $date = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $raw,
                $this->maintenant->getTimezone()
            );
        } catch (Throwable) {
            return null;
        }

        return $date === false ? null : $date;
    }

    private function typeAutorise(string $type): bool
    {
        if (in_array($type, $this->typesAutorises, true)) {
            return true;
        }

        // Préremplissage depuis un événement — voir EventsSection.vue:14-25.
        return str_starts_with($type, self::PREFIXE_EVENEMENT)
            && mb_strlen($type) > mb_strlen(self::PREFIXE_EVENEMENT);
    }
}

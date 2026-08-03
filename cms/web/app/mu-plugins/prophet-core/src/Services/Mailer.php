<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use ProphetCore\Options;
use ProphetCore\Support\Date;

final class Mailer
{
    public function __construct(private readonly TemplateRenderer $renderer)
    {
    }

    /** Port de server/utils/mailer.ts:45-146. */
    public function sendClientConfirmation(array $rdv): bool
    {
        $sujet = '✅ Confirmation de votre demande de RDV — ' . $rdv['type_consultation'];

        $corps = $this->renderer->render('emails.client', [
            'rdv' => $rdv,
            'dateFr' => Date::formatFr($rdv['date']),
            'modePaiementLabel' => Options::modePaiementLabel($rdv['mode_paiement']),
            'phone1' => Options::phone1(),
            'phone2' => Options::phone2(),
            'siteUrl' => home_url('/'),
            'annee' => (new \DateTimeImmutable('now'))->format('Y'),
        ]);

        return $this->send(
            $rdv['email'],
            $sujet,
            $corps,
            ['From: "Prophète Jeremiah Nahoum" <' . Options::env('SMTP_USER') . '>']
        );
    }

    /** Port de server/utils/mailer.ts:151-207. */
    public function sendProphetNotification(array $rdv): bool
    {
        $dateFr = Date::formatFr($rdv['date']);
        $sujet = sprintf(
            '📅 Nouveau RDV : %s %s — %s — %s',
            $rdv['prenom'],
            $rdv['nom'],
            $rdv['type_consultation'],
            $dateFr
        );

        $corps = $this->renderer->render('emails.prophet', [
            'rdv' => $rdv,
            'dateFr' => $dateFr,
            'modePaiementLabel' => Options::modePaiementLabel($rdv['mode_paiement']),
        ]);

        return $this->send(
            Options::prophetEmail(),
            $sujet,
            $corps,
            [
                'From: "Site Prophet RDV" <' . Options::env('SMTP_USER') . '>',
                'Reply-To: ' . $rdv['email'],
            ]
        );
    }

    private function send(string $to, string $sujet, string $corps, array $headers): bool
    {
        // Un destinataire vide fait échouer wp_mail sans rien dire de la cause.
        // Le cas se produit quand ni le champ « Email de notification » des
        // Réglages RDV ni PROPHET_EMAIL dans .env ne sont renseignés : la demande
        // est bien enregistrée, mais le prophète n'en est jamais averti.
        if (trim($to) === '') {
            error_log(
                '[Mailer] destinataire vide pour « ' . $sujet . ' » — renseigner'
                . ' le champ « Email de notification » des Réglages RDV ou'
                . ' PROPHET_EMAIL dans .env.'
            );

            return false;
        }

        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $envoye = (bool) wp_mail($to, $sujet, $corps, $headers);

        if (! $envoye) {
            error_log('[Mailer] envoi en échec vers ' . $to . ' — « ' . $sujet . ' »');
        }

        return $envoye;
    }
}

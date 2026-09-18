<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use ProphetCore\Support\Date;

final class Whatsapp
{
    /** Reproduit lib/utils.ts:23-26. */
    public static function url(string $phone, string $message): string
    {
        $cleaned = preg_replace('/[^+\d]/', '', $phone) ?? '';

        return 'https://wa.me/' . $cleaned . '?text=' . rawurlencode($message);
    }

    /** Reproduit server/utils/whatsapp.ts:20-38. */
    public static function confirmationMessage(array $rdv): string
    {
        $dateFr = Date::formatFr($rdv['date']);

        return <<<TXT
        Bonjour Prophète Jeremiah Nahoum,

        Je viens de soumettre une demande de rendez-vous sur votre site :

        👤 Nom : {$rdv['prenom']} {$rdv['nom']}
        🔮 Consultation : {$rdv['type_consultation']}
        📅 Date souhaitée : {$dateFr}
        ⏰ Heure : {$rdv['heure']}
        🌍 Pays : {$rdv['pays']}
        📞 Contact : {$rdv['telephone']}

        Je confirme ma demande et attends votre retour.

        Que Dieu vous bénisse.
        TXT;
    }

    public static function confirmationUrl(string $phone, array $rdv): string
    {
        return self::url($phone, self::confirmationMessage($rdv));
    }
}

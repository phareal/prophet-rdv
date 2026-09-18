<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Rdv\FlashStore;
use ProphetCore\Rdv\Validator;
use Roots\Acorn\View\Composer;

class Rdv extends Composer
{
    protected static $views = ['template-rdv', 'partials.rdv-form'];

    /**
     * FlashStore::pull() supprime le transient à la lecture, et ce composer est
     * enregistré pour deux vues : sans mémorisation, le premier rendu consomme les
     * erreurs et le formulaire s'affiche muet. On ne lit donc qu'une fois par
     * requête.
     */
    private static ?array $flashMemorise = null;

    private static bool $flashDejaLu = false;

    public function with(): array
    {
        $flash = self::flash();

        return [
            'erreurs' => $flash['errors'] ?? [],
            'valeurs' => $flash['values'] ?? [],
            'erreurGlobale' => $flash['global'] ?? '',
            'heures' => Options::heures(),
            'modes' => Options::modesPaiement(),
            'aide' => Options::content('rdv_aide'),
            'evenement' => $this->evenement(),
        ];
    }

    /** @return array{errors?: array, values?: array, global?: string}|null */
    private static function flash(): ?array
    {
        if (! self::$flashDejaLu) {
            self::$flashDejaLu = true;
            self::$flashMemorise = isset($_GET['e'])
                ? FlashStore::pull(sanitize_text_field(wp_unslash($_GET['e'])))
                : null;
        }

        return self::$flashMemorise;
    }

    /** Préremplissage depuis EventsSection — voir EventsSection.vue:14-25. */
    private function evenement(): ?array
    {
        if (empty($_GET['event'])) {
            return null;
        }

        $titre = sanitize_text_field(wp_unslash($_GET['event']));
        $lieu = sanitize_text_field(wp_unslash($_GET['eventLieu'] ?? ''));

        return [
            'titre' => $titre,
            'lieu' => $lieu,
            'heure' => sanitize_text_field(wp_unslash($_GET['eventHeure'] ?? '')),
            'date' => sanitize_text_field(wp_unslash($_GET['eventDate'] ?? '')),
            'type' => Validator::PREFIXE_EVENEMENT . $titre,
            'message' => 'Inscription pour : ' . trim($titre . ' — ' . $lieu, ' —'),
        ];
    }
}

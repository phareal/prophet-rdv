<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Don\DonSubmitHandler;
use ProphetCore\Don\MotifRepository;
use ProphetCore\Options;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Rdv\FlashStore;
use Roots\Acorn\View\Composer;
use WP_Post;

class Don extends Composer
{
    protected static $views = ['template-don', 'single-motif_paiement', 'partials.don-form'];

    /**
     * FlashStore::pull() supprime le transient à la lecture, et ce composer est
     * enregistré pour plusieurs vues rendues dans la même requête (le gabarit
     * inclut le partiel du formulaire) : sans mémorisation, le premier rendu
     * consomme les erreurs et le second s'affiche muet — piège déjà payé sur le
     * rendez-vous (App\View\Composers\Rdv).
     */
    private static ?array $flashMemorise = null;

    private static bool $flashDejaLu = false;

    public function with(): array
    {
        $flash = self::flash();
        $valeurs = $flash['values'] ?? [];

        return [
            'motifs' => MotifRepository::actifs(),
            'motifPreselectionne' => $this->motifPreselectionneId($valeurs),
            'motifIndisponible' => $this->motifIndisponible(),
            'erreurs' => $flash['errors'] ?? [],
            'valeurs' => $valeurs,
            'erreurGlobale' => $flash['global'] ?? '',
            'actionDon' => DonSubmitHandler::ACTION,
            'devise' => Options::devise(),
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

    /**
     * Un motif à présélectionner ne vient jamais d'une simple lecture de l'URL :
     * soit le lien profond (`/don/<slug>/`, requête sur le CPT `motif_paiement`)
     * pointe vers un motif actif, soit une soumission invalide vient d'être
     * renvoyée par FlashStore et doit rouvrir le même choix. Un motif désactivé
     * n'est jamais renvoyé ici — motifIndisponible() porte ce cas séparément,
     * car il exige un message, pas une sélection dans une liste qui ne le
     * contient plus.
     */
    private function motifPreselectionneId(array $valeurs): ?int
    {
        $motifDuLien = $this->motifDuLienProfond();

        if ($motifDuLien !== null && $motifDuLien['actif']) {
            return $motifDuLien['id'];
        }

        return isset($valeurs['motif']) && $valeurs['motif'] !== '' ? (int) $valeurs['motif'] : null;
    }

    /** Titre du motif visé par un lien profond désactivé, sinon null. */
    private function motifIndisponible(): ?string
    {
        $motif = $this->motifDuLienProfond();

        return ($motif !== null && ! $motif['actif']) ? $motif['titre'] : null;
    }

    /** @return array<string, mixed>|null */
    private function motifDuLienProfond(): ?array
    {
        if (! is_singular(MotifPaiement::SLUG)) {
            return null;
        }

        $post = get_queried_object();

        return $post instanceof WP_Post ? MotifRepository::parSlug($post->post_name) : null;
    }
}

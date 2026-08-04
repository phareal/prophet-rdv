<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use ProphetCore\PostTypes\RendezVous;

final class Abandon
{
    public const HOOK = 'prophet_paiement_abandon';

    public static function register(): void
    {
        add_action(self::HOOK, static function (): void {
            (new self())->passer();
        });

        add_action('init', static function (): void {
            if (! wp_next_scheduled(self::HOOK)) {
                wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::HOOK);
            }
        });
    }

    /**
     * En mode exigé, un rendez-vous laissé sans règlement bloquerait son créneau
     * indéfiniment. L'annuler le libère — `Repository::slotTaken()` ne compte pas
     * les rendez-vous annulés.
     *
     * @return int nombre de rendez-vous annulés
     */
    public function passer(): int
    {
        if (Options::momentPaiement() !== 'exige') {
            return 0;
        }

        $limite = gmdate('Y-m-d H:i:s', time() - Options::delaiAbandon() * MINUTE_IN_SECONDS);

        $candidats = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => [RendezVous::STATUT_EN_ATTENTE],
            'fields' => 'ids',
            'posts_per_page' => 50,
            'no_found_rows' => true,
            'date_query' => [['before' => $limite, 'column' => 'post_date_gmt']],
            'meta_query' => [
                ['key' => RendezVous::metaKey('paiement_statut'), 'value' => Statut::EN_ATTENTE],
            ],
        ]);

        $paiements = new PaiementRepository();
        $annules = 0;

        foreach ($candidats as $postId) {
            wp_update_post([
                'ID' => (int) $postId,
                'post_status' => RendezVous::STATUT_ANNULE,
            ]);

            $paiements->appliquerStatut((int) $postId, Statut::ANNULE);
            $annules++;
        }

        if ($annules > 0) {
            error_log('[Paiement] ' . $annules . ' rendez-vous annulés faute de règlement');
        }

        return $annules;
    }
}

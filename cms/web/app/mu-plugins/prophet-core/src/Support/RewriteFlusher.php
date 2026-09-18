<?php

declare(strict_types=1);

namespace ProphetCore\Support;

/**
 * WordPress ne régénère la table de règles de permaliens qu'au déclenchement
 * manuel (Réglages → Permaliens) ou par flush_rewrite_rules(). Un déploiement
 * qui change une règle de réécriture (CPT motif_paiement, add_rewrite_rule
 * de /don/merci/…) sans passer par cet écran laisse /don/<slug>/ et
 * /don/merci/ en 404 — chaque QR déjà imprimé et chaque lien déjà partagé
 * cassent, jusqu'à ce que quelqu'un pense à rafraîchir les permaliens à la
 * main.
 *
 * Le flush régénère toute la table et est coûteux : il ne doit tourner
 * qu'une fois par changement de structure, jamais à chaque requête. Une
 * version stockée en option, comparée à la version courante, ne déclenche
 * le flush que lorsqu'elles divergent.
 */
final class RewriteFlusher
{
    private const OPTION = 'prophet_rewrite_version';

    /**
     * À incrémenter chaque fois qu'une règle de réécriture change (nouveau
     * CPT public, nouvel add_rewrite_rule, changement de slug…).
     */
    private const VERSION = '1';

    public static function register(): void
    {
        // Priorité après celle des types de publication (10, par défaut) :
        // synchroniser plus tôt flusherait une table de règles pas encore à
        // jour avec les CPT tout juste enregistrés.
        add_action('init', [self::class, 'synchroniser'], 20);
    }

    public static function synchroniser(): void
    {
        if ((string) get_option(self::OPTION, '') === self::VERSION) {
            return;
        }

        flush_rewrite_rules();
        update_option(self::OPTION, self::VERSION);
    }
}

<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Tests\TestCase;

final class MotifPaiementTest extends TestCase
{
    /**
     * La page de remerciement du don (/don/merci/) vit sous le même préfixe
     * /don/ que le lien profond de chaque motif. La règle générique posée
     * par le rewrite du CPT (don/([^/]+)/?$) matcherait /don/merci/ en
     * premier et tenterait d'y résoudre un motif nommé « merci » — 404 avant
     * même d'envisager la page. Sans une règle statique prioritaire
     * ('top'), la page de remerciement ne serait jamais atteinte.
     */
    public function test_une_regle_prioritaire_reserve_don_merci_a_la_page(): void
    {
        $reglesAjoutees = [];

        Functions\when('register_post_type')->justReturn(true);
        Functions\when('add_rewrite_rule')->alias(function ($regex, $query, $after = 'bottom') use (&$reglesAjoutees) {
            $reglesAjoutees[] = [$regex, $query, $after];
        });
        Functions\when('add_action')->alias(function ($hook, $callback) {
            if ($hook === 'init') {
                $callback();
            }
        });
        Functions\when('add_filter')->justReturn(true);

        MotifPaiement::register();

        $this->assertContains(
            ['^don/merci/?$', 'index.php?pagename=don/merci', 'top'],
            $reglesAjoutees
        );
    }
}

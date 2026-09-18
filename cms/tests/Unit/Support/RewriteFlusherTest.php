<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Support;

use Brain\Monkey\Functions;
use ProphetCore\Support\RewriteFlusher;
use ProphetCore\Tests\TestCase;

final class RewriteFlusherTest extends TestCase
{
    /**
     * Sans ce garde-fou, /don/<slug>/ et /don/merci/ répondent 404 après
     * chaque déploiement qui touche une règle de réécriture — chaque QR déjà
     * imprimé et chaque lien déjà partagé cassent, jusqu'à ce que quelqu'un
     * ouvre Réglages → Permaliens à la main.
     */
    public function test_le_flush_est_declenche_quand_la_version_stockee_differe(): void
    {
        $flushAppele = false;
        Functions\when('get_option')->justReturn('0');
        Functions\when('flush_rewrite_rules')->alias(function () use (&$flushAppele) {
            $flushAppele = true;
        });
        Functions\when('update_option')->justReturn(true);

        RewriteFlusher::synchroniser();

        $this->assertTrue($flushAppele);
    }

    public function test_la_nouvelle_version_est_enregistree_apres_le_flush(): void
    {
        Functions\when('get_option')->justReturn('0');
        Functions\when('flush_rewrite_rules')->justReturn(null);
        $enregistre = [];
        Functions\when('update_option')->alias(function ($cle, $valeur) use (&$enregistre) {
            $enregistre[$cle] = $valeur;

            return true;
        });

        RewriteFlusher::synchroniser();

        $this->assertArrayHasKey('prophet_rewrite_version', $enregistre);
        $this->assertNotSame('0', $enregistre['prophet_rewrite_version']);
    }

    /**
     * Le flush est coûteux (régénère toute la table de règles) : il ne doit
     * tourner qu'une fois par changement de structure, jamais à chaque
     * requête.
     */
    public function test_aucun_flush_si_la_version_stockee_correspond_deja(): void
    {
        $version = null;
        Functions\when('update_option')->alias(function ($cle, $valeur) use (&$version) {
            $version = $valeur;

            return true;
        });
        Functions\when('get_option')->justReturn('0');
        Functions\when('flush_rewrite_rules')->justReturn(null);
        RewriteFlusher::synchroniser();

        $flushAppele = false;
        Functions\when('get_option')->justReturn($version);
        Functions\when('flush_rewrite_rules')->alias(function () use (&$flushAppele) {
            $flushAppele = true;
        });

        RewriteFlusher::synchroniser();

        $this->assertFalse($flushAppele);
    }

    /**
     * L'enregistrement doit tourner après celui des types de publication
     * (priorité par défaut 10 sur `init`) : synchroniser trop tôt flusherait
     * une table de règles pas encore à jour.
     */
    public function test_l_enregistrement_accroche_la_synchronisation_apres_les_types_sur_init(): void
    {
        $priorite = null;
        Functions\when('add_action')->alias(function ($hook, $callback, $prio = 10) use (&$priorite) {
            if ($hook === 'init') {
                $priorite = $prio;
            }
        });

        RewriteFlusher::register();

        $this->assertGreaterThan(10, $priorite);
    }
}

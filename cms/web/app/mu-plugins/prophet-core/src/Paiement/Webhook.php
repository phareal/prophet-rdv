<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use WP_REST_Request;
use WP_REST_Response;

final class Webhook
{
    public const ESPACE = 'prophet/v1';
    public const ROUTE = '/moneroo/webhook';
    private const ENTETE_SIGNATURE = 'x-moneroo-signature';

    public static function register(): void
    {
        add_action('rest_api_init', static function (): void {
            register_rest_route(self::ESPACE, self::ROUTE, [
                'methods' => 'POST',
                // La route est publique par nature : Moneroo n'a pas de compte
                // WordPress. L'authentification, c'est la signature.
                'permission_callback' => '__return_true',
                'callback' => static function (WP_REST_Request $requete): WP_REST_Response {
                    $code = (new self())->traiter(
                        $requete->get_body(),
                        (string) $requete->get_header(self::ENTETE_SIGNATURE),
                    );

                    return new WP_REST_Response(null, $code);
                },
            ]);
        });
    }

    /**
     * Comparaison à temps constant : une comparaison naïve laisse fuir la
     * signature attendue par la mesure du temps de réponse.
     */
    public static function signatureValide(string $corpsBrut, string $signature, string $secret): bool
    {
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $corpsBrut, $secret), $signature);
    }

    /** @return int le code HTTP à renvoyer */
    public function traiter(string $corpsBrut, string $signature): int
    {
        if (! self::signatureValide($corpsBrut, $signature, Options::env('MONEROO_WEBHOOK_SECRET'))) {
            error_log('[Paiement] webhook à signature invalide rejeté');

            return 403;
        }

        $charge = json_decode($corpsBrut, true);

        if (! is_array($charge) || ! isset($charge['data']['id'])) {
            error_log('[Paiement] webhook au corps illisible');

            return 400;
        }

        $paiementId = (string) $charge['data']['id'];
        $sujet = ResolveurDeSujet::parPaiementId($paiementId);

        if ($sujet === null) {
            // Acquitté sans traitement : sans quoi Moneroo réessaierait trois
            // fois un événement qui ne nous concerne pas.
            error_log('[Paiement] webhook pour une transaction inconnue : ' . $paiementId);

            return 200;
        }

        $paiements = new PaiementRepository($sujet->metaKey(''), $sujet->typeDePublication());

        $paiements->appliquerStatut(
            $sujet->postId(),
            Statut::depuisMoneroo((string) ($charge['data']['status'] ?? '')),
            (string) ($charge['data']['payment_method'] ?? ''),
        );

        return 200;
    }
}

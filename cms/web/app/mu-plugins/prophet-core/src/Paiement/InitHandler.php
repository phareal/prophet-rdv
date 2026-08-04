<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;

class InitHandler
{
    public const ACTION = 'prophet_paiement_init';

    public static function register(): void
    {
        $handler = new self();

        add_action('admin_post_' . self::ACTION, [$handler, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$handler, 'handle']);
    }

    public function handle(): void
    {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : '';

        try {
            $url = $this->demarrer($ref);
        } catch (MonerooException $e) {
            error_log('[Paiement] initialisation impossible (réf. ' . $ref . ') : ' . $e->getMessage());
            wp_safe_redirect(add_query_arg(
                ['ref' => $ref, 'paiement' => 'erreur'],
                home_url('/confirmation/')
            ));
            $this->terminer();

            return;
        }

        wp_redirect($url);
        $this->terminer();
    }

    /**
     * @return string l'URL de paiement Moneroo
     * @throws MonerooException
     */
    public function demarrer(string $ref): string
    {
        $sujet = ResolveurDeSujet::parReference($ref);

        if ($sujet === null) {
            throw new MonerooException('Référence inconnue');
        }

        $paiements = new PaiementRepository($sujet->metaKey(''), $sujet->typeDePublication());

        if ($paiements->statut($sujet->postId()) === Statut::PAYE) {
            throw new MonerooException('Déjà réglé');
        }

        $montant = $sujet->montant();

        if ($montant === null) {
            throw new MonerooException('Aucun montant à régler');
        }

        $client = $sujet->client();

        $transaction = (new Moneroo(Options::env('MONEROO_SECRET_KEY')))->initialiser([
            'amount' => $montant['montant'],
            'currency' => $montant['devise'],
            'description' => $sujet->description(),
            'customer' => [
                'email' => $client['email'],
                'first_name' => $client['prenom'],
                'last_name' => $client['nom'],
                'phone' => $client['telephone'],
            ],
            // L'URL de retour ne porte que la référence : un identifiant de
            // publication y serait énumérable.
            'return_url' => $sujet->urlRetour(),
            'metadata' => ['ref' => $ref],
        ]);

        $paiements->enregistrerInitialisation(
            $sujet->postId(),
            $transaction['id'],
            $montant['montant'],
            $montant['devise'],
        );

        return $transaction['checkout_url'];
    }

    protected function terminer(): void
    {
        exit;
    }
}

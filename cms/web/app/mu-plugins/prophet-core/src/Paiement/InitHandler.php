<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use ProphetCore\Rdv\Repository;

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
        $rdv = $this->chargerRendezVous($ref);

        if ($rdv === null) {
            throw new MonerooException('Référence de rendez-vous inconnue');
        }

        $postId = (int) $rdv['post_id'];
        $paiements = new PaiementRepository();

        if ($paiements->statut($postId) === Statut::PAYE) {
            throw new MonerooException('Rendez-vous déjà réglé');
        }

        $montant = Montant::pour((string) $rdv['type_consultation']);

        if ($montant === null) {
            throw new MonerooException('Aucun montant à régler pour ce rendez-vous');
        }

        $client = new Moneroo(Options::env('MONEROO_SECRET_KEY'));

        $transaction = $client->initialiser([
            'amount' => $montant['montant'],
            'currency' => $montant['devise'],
            'description' => 'Consultation — ' . $rdv['type_consultation'],
            'customer' => [
                'email' => $rdv['email'],
                'first_name' => $rdv['prenom'],
                'last_name' => $rdv['nom'],
                'phone' => $rdv['telephone'],
            ],
            // L'URL de retour ne porte que la référence : un identifiant de
            // publication y serait énumérable.
            'return_url' => add_query_arg(['ref' => $ref], home_url('/confirmation/')),
            'metadata' => ['ref' => $ref],
        ]);

        $paiements->enregistrerInitialisation(
            $postId,
            $transaction['id'],
            $montant['montant'],
            $montant['devise'],
        );

        return $transaction['checkout_url'];
    }

    /** @return array<string, mixed>|null */
    protected function chargerRendezVous(string $ref): ?array
    {
        return (new Repository())->findByRef($ref);
    }

    protected function terminer(): void
    {
        exit;
    }
}

<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\MonerooException;
use ProphetCore\Rdv\FlashStore;
use ProphetCore\Support\Journal;
use Throwable;

class DonSubmitHandler
{
    public const ACTION = 'prophet_don_submit';
    public const NONCE = 'prophet_don';
    public const HONEYPOT = 'prophet_site';

    /**
     * Bien plus permissive que celle du rendez-vous, et portée sur le couple
     * (IP, email) : une limite par IP seule bloquerait une assemblée entière
     * scannant le même QR depuis un seul réseau.
     */
    private const DELAI_ENTRE_ENVOIS = 20;

    public static function register(): void
    {
        $handler = new self();

        add_action('admin_post_' . self::ACTION, [$handler, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$handler, 'handle']);
    }

    public function handle(): void
    {
        $this->process(wp_unslash($_POST), (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    public function process(array $post, string $ip): void
    {
        if (self::isBot($post)) {
            $this->rediriger(['global' => 'Votre don n\'a pas pu être traité.'], $post);

            return;
        }

        if (! wp_verify_nonce((string) ($post['prophet_nonce'] ?? ''), self::NONCE)) {
            $this->rediriger(['global' => 'Votre session a expiré. Merci de renvoyer le formulaire.'], $post);

            return;
        }

        $email = strtolower(trim((string) ($post['email'] ?? '')));

        if ($ip !== '' && $email !== '' && get_transient(self::cleDeLimite($ip, $email)) !== false) {
            $this->rediriger(['global' => 'Vous venez déjà d\'envoyer un don. Patientez un instant.'], $post);

            return;
        }

        $motif = MotifRepository::parId((int) ($post['motif'] ?? 0));

        if ($motif === null || ! $motif['actif']) {
            $this->rediriger(['motif' => 'Veuillez choisir un motif de paiement.'], $post);

            return;
        }

        $montant = MontantDon::resoudre($motif, (string) ($post['montant'] ?? ''));

        if (isset($montant['erreur'])) {
            $this->rediriger(['montant' => $montant['erreur']], $post);

            return;
        }

        $erreurs = $this->validerDonateur($post);

        if ($erreurs !== []) {
            $this->rediriger($erreurs, $post);

            return;
        }

        try {
            $ref = (new DonRepository())->creer([
                'motif_id' => $motif['id'],
                'motif_titre' => $motif['titre'],
                'montant' => $montant['montant'],
                'devise' => $montant['devise'],
                'nom' => $post['nom'], 'prenom' => $post['prenom'],
                'email' => $email, 'telephone' => $post['telephone'] ?? '',
                'message' => $post['message'] ?? '',
            ]);
        } catch (Throwable $e) {
            error_log('[Don] enregistrement en échec : ' . $e->getMessage());
            $this->rediriger(['global' => 'Une erreur interne est survenue. Veuillez réessayer.'], $post);

            return;
        }

        if ($ip !== '') {
            set_transient(self::cleDeLimite($ip, $email), 1, self::DELAI_ENTRE_ENVOIS);
        }

        // Le don est enregistré : un échec d'initialisation ne le perd pas.
        try {
            wp_redirect((new InitHandler())->demarrer($ref));
            $this->terminer();

            return;
        } catch (MonerooException $e) {
            error_log(
                '[Don] paiement non initialisable (réf. ' . Journal::tronquerReference($ref) . '…) : '
                . $e->getMessage()
            );
        }

        wp_safe_redirect(add_query_arg(['ref' => $ref, 'paiement' => 'erreur'], home_url('/don/merci/')));
        $this->terminer();
    }

    public static function isBot(array $post): bool
    {
        return trim((string) ($post[self::HONEYPOT] ?? '')) !== '';
    }

    public static function cleDeLimite(string $ip, string $email): string
    {
        return 'prophet_don_' . md5($ip . '|' . strtolower(trim($email)));
    }

    /** @return array<string, string> */
    private function validerDonateur(array $post): array
    {
        $erreurs = [];

        if (mb_strlen(trim((string) ($post['prenom'] ?? ''))) < 2) {
            $erreurs['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        if (mb_strlen(trim((string) ($post['nom'] ?? ''))) < 2) {
            $erreurs['nom'] = 'Le nom doit contenir au moins 2 caractères';
        }

        if (filter_var((string) ($post['email'] ?? ''), FILTER_VALIDATE_EMAIL) === false) {
            $erreurs['email'] = 'Adresse email invalide';
        }

        return $erreurs;
    }

    private function rediriger(array $erreurs, array $valeurs): void
    {
        unset($valeurs['prophet_nonce'], $valeurs[self::HONEYPOT], $valeurs['action']);

        $global = $erreurs['global'] ?? '';
        unset($erreurs['global']);

        $cle = FlashStore::put(['errors' => $erreurs, 'values' => $valeurs, 'global' => $global]);

        wp_safe_redirect(add_query_arg(['e' => $cle], home_url('/don/')) . '#don');
        $this->terminer();
    }

    protected function terminer(): void
    {
        exit;
    }
}

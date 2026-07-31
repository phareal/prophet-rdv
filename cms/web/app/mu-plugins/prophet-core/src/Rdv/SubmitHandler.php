<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use ProphetCore\Options;
use ProphetCore\PostTypes\Service;
use ProphetCore\Services\BladeRenderer;
use ProphetCore\Services\Mailer;
use Throwable;

class SubmitHandler
{
    public const ACTION = 'prophet_rdv_submit';
    public const NONCE = 'prophet_rdv';
    public const HONEYPOT = 'prophet_website';
    private const DELAI_ENTRE_ENVOIS = 60;

    public static function register(): void
    {
        $handler = new self();

        add_action('admin_post_' . self::ACTION, [$handler, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$handler, 'handle']);
    }

    public function handle(): void
    {
        $this->process(
            wp_unslash($_POST),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        );
    }

    public function process(array $post, string $ip): void
    {
        // Les robots reçoivent la page de confirmation générique : aucun signal utile.
        if (self::isBot($post)) {
            $this->redirigerVersFormulaire(['global' => 'Votre demande n\'a pas pu être traitée.'], $post);

            return;
        }

        if (! wp_verify_nonce((string) ($post['prophet_nonce'] ?? ''), self::NONCE)) {
            $this->redirigerVersFormulaire(
                ['global' => 'Votre session a expiré. Merci de renvoyer le formulaire.'],
                $post,
            );

            return;
        }

        if ($ip !== '' && get_transient(self::rateLimitKey($ip)) !== false) {
            $this->redirigerVersFormulaire(
                ['global' => 'Vous venez déjà d\'envoyer une demande. Patientez une minute.'],
                $post,
            );

            return;
        }

        $validator = new Validator(
            Options::heures(),
            $this->typesDeConsultation(),
            array_column(Options::modesPaiement(), 'value'),
        );

        $resultat = $validator->validate($post);

        if (! $resultat->isValid()) {
            $this->redirigerVersFormulaire($resultat->errors(), $post);

            return;
        }

        $data = $resultat->data();
        $repository = new Repository();

        if ($repository->slotTaken($data['date']->format('Y-m-d'), $data['heure'])) {
            $this->redirigerVersFormulaire(
                ['heure' => 'Ce créneau est déjà réservé. Veuillez choisir une autre heure.'],
                $post,
            );

            return;
        }

        try {
            $ref = $repository->create($data);
        } catch (Throwable $e) {
            error_log('[RDV] enregistrement en échec : ' . $e->getMessage());
            $this->redirigerVersFormulaire(
                ['global' => 'Une erreur interne est survenue. Veuillez réessayer.'],
                $post,
            );

            return;
        }

        if ($ip !== '') {
            set_transient(self::rateLimitKey($ip), 1, self::DELAI_ENTRE_ENVOIS);
        }

        // Les emails ne doivent pas retarder la redirection ; un échec est journalisé
        // sans faire échouer la demande, comme server/api/rdv.post.ts:87-95.
        add_action('shutdown', static function () use ($data): void {
            $mailer = new Mailer(new BladeRenderer());
            $mailer->sendClientConfirmation($data);
            $mailer->sendProphetNotification($data);
        });

        wp_safe_redirect(add_query_arg(['ref' => $ref], home_url('/confirmation')));
        $this->terminer();

        return;
    }

    public static function isBot(array $post): bool
    {
        return trim((string) ($post[self::HONEYPOT] ?? '')) !== '';
    }

    public static function rateLimitKey(string $ip): string
    {
        return 'prophet_rdv_ip_' . md5($ip);
    }

    /** @return array<int, string> */
    private function typesDeConsultation(): array
    {
        $titres = get_posts([
            'post_type' => Service::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return array_map(static fn($post): string => (string) $post->post_title, $titres);
    }

    private function redirigerVersFormulaire(array $errors, array $values): void
    {
        unset($values['prophet_nonce'], $values[self::HONEYPOT], $values['action']);

        $global = $errors['global'] ?? '';
        unset($errors['global']);

        $cle = FlashStore::put([
            'errors' => $errors,
            'values' => $values,
            'global' => $global,
        ]);

        wp_safe_redirect(add_query_arg(['e' => $cle], home_url('/rdv')) . '#formulaire');
        $this->terminer();

        return;
    }

    protected function terminer(): void
    {
        exit;
    }
}

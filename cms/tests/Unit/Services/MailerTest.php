<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Services\Mailer;
use ProphetCore\Services\TemplateRenderer;
use ProphetCore\Tests\TestCase;

final class MailerTest extends TestCase
{
    private array $envois = [];

    protected function setUp(): void
    {
        parent::setUp();

        // sendClientConfirmation() construit l'URL du site via home_url() : la
        // fonction WordPress n'existe pas dans le harnais de tests, il faut la
        // simuler pour tous les tests de cette classe.
        Functions\when('home_url')->justReturn('https://example.test/');
    }

    private function renderer(): TemplateRenderer
    {
        return new class () implements TemplateRenderer {
            public function render(string $view, array $data): string
            {
                return '<html>' . $view . '</html>';
            }
        };
    }

    private function rdv(): array
    {
        return [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.test',
            'telephone' => '+22890000000',
            'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-07-30 10:00:00', new DateTimeZone('UTC')),
            'heure' => '10h00',
            'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money',
            'message' => '',
        ];
    }

    private function capturerLesEnvois(): void
    {
        $this->envois = [];

        Functions\when('wp_mail')->alias(function ($to, $subject, $body, $headers) {
            $this->envois[] = compact('to', 'subject', 'body', 'headers');

            return true;
        });
        Functions\when('carbon_get_theme_option')->justReturn('');
    }

    public function test_l_email_client_part_a_la_bonne_adresse_avec_le_bon_sujet(): void
    {
        $this->capturerLesEnvois();
        $_ENV['SMTP_USER'] = 'site@example.test';

        (new Mailer($this->renderer()))->sendClientConfirmation($this->rdv());

        $this->assertCount(1, $this->envois);
        $this->assertSame('jane@example.test', $this->envois[0]['to']);
        $this->assertSame(
            '✅ Confirmation de votre demande de RDV — Mariage',
            $this->envois[0]['subject']
        );
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $this->envois[0]['headers']);

        unset($_ENV['SMTP_USER']);
    }

    public function test_la_notification_part_au_prophete_avec_un_reply_to_client(): void
    {
        $this->capturerLesEnvois();
        $_ENV['PROPHET_EMAIL'] = 'prophete@example.test';
        $_ENV['SMTP_USER'] = 'site@example.test';

        (new Mailer($this->renderer()))->sendProphetNotification($this->rdv());

        $this->assertSame('prophete@example.test', $this->envois[0]['to']);
        $this->assertSame(
            '📅 Nouveau RDV : Jane Doe — Mariage — jeudi 30 juillet 2026',
            $this->envois[0]['subject']
        );
        $this->assertContains('Reply-To: jane@example.test', $this->envois[0]['headers']);

        unset($_ENV['PROPHET_EMAIL'], $_ENV['SMTP_USER']);
    }

    public function test_un_echec_d_envoi_est_journalise_et_ne_leve_pas_d_exception(): void
    {
        $this->envois = [];
        Functions\when('carbon_get_theme_option')->justReturn('');
        Functions\when('wp_mail')->justReturn(false);
        Functions\when('error_log')->justReturn(true);

        $resultat = (new Mailer($this->renderer()))->sendClientConfirmation($this->rdv());

        $this->assertFalse($resultat);
    }
}

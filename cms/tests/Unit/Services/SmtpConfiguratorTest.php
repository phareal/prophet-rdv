<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use ProphetCore\Services\SmtpConfigurator;
use ProphetCore\Tests\TestCase;

final class SmtpConfiguratorTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['SMTP_HOST'], $_ENV['SMTP_USER'], $_ENV['SMTP_PORT'], $_ENV['SMTP_PASS'], $_ENV['SMTP_SECURE']);

        parent::tearDown();
    }

    public function test_un_delai_court_est_impose_pour_ne_pas_bloquer_le_visiteur(): void
    {
        $_ENV['SMTP_HOST'] = 'smtp.example.test';
        $_ENV['SMTP_USER'] = 'site@example.test';

        $phpmailer = new class () {
            public bool $isSmtpAppele = false;

            public string $Host = '';

            public int $Port = 0;

            public bool $SMTPAuth = false;

            public string $Username = '';

            public string $Password = '';

            public string $SMTPSecure = '';

            public string $CharSet = '';

            public int $Timeout = 300; // valeur par défaut de PHPMailer

            public function isSMTP(): void
            {
                $this->isSmtpAppele = true;
            }
        };

        Functions\when('add_action')->alias(fn($hook, $callback) => $callback($phpmailer));

        SmtpConfigurator::register();

        $this->assertTrue($phpmailer->isSmtpAppele);
        $this->assertSame(10, $phpmailer->Timeout);
    }

    public function test_sans_smtp_configure_wordpress_garde_son_envoi_par_defaut(): void
    {
        $phpmailer = new class () {
            public bool $isSmtpAppele = false;

            public int $Timeout = 300;

            public function isSMTP(): void
            {
                $this->isSmtpAppele = true;
            }
        };

        Functions\when('add_action')->alias(fn($hook, $callback) => $callback($phpmailer));

        SmtpConfigurator::register();

        $this->assertFalse($phpmailer->isSmtpAppele);
        $this->assertSame(300, $phpmailer->Timeout);
    }
}

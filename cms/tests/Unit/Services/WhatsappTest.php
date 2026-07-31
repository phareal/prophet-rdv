<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Services\Whatsapp;
use ProphetCore\Tests\TestCase;

final class WhatsappTest extends TestCase
{
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

    public function test_le_numero_est_nettoye_et_le_message_encode(): void
    {
        $url = Whatsapp::url('+228 97 16 90 90', 'Bonjour à vous');

        $this->assertSame('https://wa.me/+22897169090?text=Bonjour%20%C3%A0%20vous', $url);
    }

    public function test_le_message_reprend_les_champs_du_rendez_vous(): void
    {
        $message = Whatsapp::confirmationMessage($this->rdv());

        $this->assertStringContainsString('👤 Nom : Jane Doe', $message);
        $this->assertStringContainsString('🔮 Consultation : Mariage', $message);
        $this->assertStringContainsString('📅 Date souhaitée : jeudi 30 juillet 2026', $message);
        $this->assertStringContainsString('⏰ Heure : 10h00', $message);
        $this->assertStringContainsString('🌍 Pays : Togo', $message);
        $this->assertStringContainsString('📞 Contact : +22890000000', $message);
        $this->assertStringContainsString('Que Dieu vous bénisse.', $message);
    }

    public function test_l_url_de_confirmation_combine_numero_et_message(): void
    {
        $url = Whatsapp::confirmationUrl('+22897169090', $this->rdv());

        $this->assertStringStartsWith('https://wa.me/+22897169090?text=', $url);
        $this->assertStringContainsString(rawurlencode('Jane Doe'), $url);
    }
}

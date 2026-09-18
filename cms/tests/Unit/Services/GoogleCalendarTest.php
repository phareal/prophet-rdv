<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use ProphetCore\Services\GoogleCalendar;
use ProphetCore\Tests\TestCase;

final class GoogleCalendarTest extends TestCase
{
    public function test_un_evenement_horaire_est_mis_en_forme_comme_le_bridge(): void
    {
        $items = [[
            'summary' => 'Nuit de Prophétie',
            'description' => "lieu: Paris, France\nplaces: Entrée libre\ntype: Croisade",
            'start' => ['dateTime' => '2026-04-18T20:00:00+00:00'],
            'end' => ['dateTime' => '2026-04-19T00:00:00+00:00'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertCount(1, $events);
        $this->assertSame('18', $events[0]['day']);
        $this->assertSame('Avr', $events[0]['month']);
        $this->assertSame('2026', $events[0]['year']);
        $this->assertSame('Nuit de Prophétie', $events[0]['title']);
        $this->assertSame('Paris, France', $events[0]['lieu']);
        $this->assertSame('20h00 – 00h00', $events[0]['heure']);
        $this->assertSame('Entrée libre', $events[0]['places']);
        $this->assertSame('Croisade', $events[0]['type']);
        $this->assertTrue($events[0]['featured']);
    }

    public function test_la_description_html_est_nettoyee_avant_extraction(): void
    {
        $items = [[
            'summary' => 'Conférence',
            'description' => '<p>lieu: Abidjan</p><br><p>places: Places limitées</p>',
            'start' => ['dateTime' => '2026-04-26T09:00:00+00:00'],
            'end' => ['dateTime' => '2026-04-26T17:00:00+00:00'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertSame('Abidjan', $events[0]['lieu']);
        $this->assertSame('Places limitées', $events[0]['places']);
    }

    public function test_un_evenement_sur_la_journee_entiere_affiche_deux_jours(): void
    {
        $items = [[
            'summary' => 'Retraite',
            'description' => '',
            'start' => ['date' => '2026-05-24'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertSame('2 jours', $events[0]['heure']);
    }

    public function test_les_valeurs_par_defaut_reprennent_celles_du_bridge(): void
    {
        $items = [[
            'summary' => 'Sans description',
            'location' => 'Lomé',
            'start' => ['dateTime' => '2026-06-01T18:00:00+00:00'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertSame('Lomé', $events[0]['lieu']);
        $this->assertSame('Entrée libre', $events[0]['places']);
        $this->assertSame('Conférence', $events[0]['type']);
        $this->assertSame('18h00', $events[0]['heure']);
    }

    public function test_les_entrees_sans_titre_sont_ignorees_et_seule_la_premiere_est_mise_en_avant(): void
    {
        $items = [
            ['description' => '', 'start' => ['dateTime' => '2026-06-01T18:00:00+00:00']],
            ['summary' => 'A', 'description' => '', 'start' => ['dateTime' => '2026-06-02T18:00:00+00:00']],
            ['summary' => 'B', 'description' => '', 'start' => ['dateTime' => '2026-06-03T18:00:00+00:00']],
        ];

        $events = GoogleCalendar::parseItems($items);

        $this->assertCount(2, $events);
        $this->assertTrue($events[0]['featured']);
        $this->assertFalse($events[1]['featured']);
    }

    public function test_sans_cle_api_le_service_renvoie_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\expect('set_transient')
            ->once()
            ->with(GoogleCalendar::TRANSIENT, GoogleCalendar::MOCK_EVENTS, GoogleCalendar::TTL_ECHEC)
            ->andReturn(true);

        $events = (new GoogleCalendar('', ''))->events();

        $this->assertSame(GoogleCalendar::MOCK_EVENTS, $events);
    }

    public function test_une_erreur_http_retombe_sur_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('wp_remote_get')->justReturn('erreur');
        Functions\when('error_log')->justReturn(true);
        Functions\expect('set_transient')
            ->once()
            ->with(GoogleCalendar::TRANSIENT, GoogleCalendar::MOCK_EVENTS, GoogleCalendar::TTL_ECHEC)
            ->andReturn(true);

        $events = (new GoogleCalendar('cle', 'agenda'))->events();

        $this->assertSame(GoogleCalendar::MOCK_EVENTS, $events);
    }

    public function test_une_reponse_inattendue_retombe_sur_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn(['body' => '{"foo":"bar"}']);
        Functions\when('wp_remote_retrieve_body')->justReturn('{"foo":"bar"}');
        Functions\when('error_log')->justReturn(true);
        Functions\expect('set_transient')
            ->once()
            ->with(GoogleCalendar::TRANSIENT, GoogleCalendar::MOCK_EVENTS, GoogleCalendar::TTL_ECHEC)
            ->andReturn(true);

        $events = (new GoogleCalendar('cle', 'agenda'))->events();

        $this->assertSame(GoogleCalendar::MOCK_EVENTS, $events);
    }

    public function test_un_appel_reussi_renvoie_les_evenements_et_utilise_le_ttl_normal(): void
    {
        $body = json_encode([
            'items' => [[
                'summary' => 'Culte de Puissance',
                'description' => "lieu: Lomé, Togo\nplaces: Entrée libre\ntype: Croisade",
                'start' => ['dateTime' => '2026-08-01T18:00:00+00:00'],
                'end' => ['dateTime' => '2026-08-01T20:00:00+00:00'],
            ]],
        ]);

        Functions\when('get_transient')->justReturn(false);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn(['body' => $body]);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\expect('set_transient')
            ->once()
            ->with(GoogleCalendar::TRANSIENT, \Mockery::type('array'), GoogleCalendar::TTL)
            ->andReturn(true);

        $events = (new GoogleCalendar('cle', 'agenda'))->events();

        $this->assertCount(1, $events);
        $this->assertSame('Culte de Puissance', $events[0]['title']);
        $this->assertSame('01', $events[0]['day']);
        $this->assertSame('Lomé, Togo', $events[0]['lieu']);
        $this->assertSame('18h00 – 20h00', $events[0]['heure']);
        $this->assertTrue($events[0]['featured']);
    }

    public function test_le_cache_est_servi_sans_appel_http(): void
    {
        Functions\when('get_transient')->justReturn([['title' => 'depuis le cache']]);
        Functions\expect('wp_remote_get')->never();

        $events = (new GoogleCalendar('cle', 'agenda'))->events();

        $this->assertSame('depuis le cache', $events[0]['title']);
    }
}

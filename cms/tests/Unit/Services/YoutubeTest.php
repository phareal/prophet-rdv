<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use ProphetCore\Services\Youtube;
use ProphetCore\Tests\TestCase;

final class YoutubeTest extends TestCase
{
    public function test_l_identifiant_de_playlist_derive_de_l_identifiant_de_chaine(): void
    {
        $this->assertSame('UUabc123', Youtube::uploadsPlaylistId('UCabc123'));
    }

    public function test_seule_la_premiere_occurrence_de_uc_est_remplacee(): void
    {
        $this->assertSame('UUxUCy', Youtube::uploadsPlaylistId('UCxUCy'));
    }

    public function test_la_miniature_haute_definition_est_preferee(): void
    {
        $items = [[
            'snippet' => [
                'title' => 'Prophétie sur les nations',
                'description' => 'Description de la vidéo.',
                'resourceId' => ['videoId' => 'abc'],
                'thumbnails' => [
                    'high' => ['url' => 'https://i.ytimg.com/high.jpg'],
                    'medium' => ['url' => 'https://i.ytimg.com/medium.jpg'],
                ],
            ],
        ]];

        $videos = Youtube::parseItems($items);

        $this->assertSame('abc', $videos[0]['id']);
        $this->assertSame('Prophétie sur les nations', $videos[0]['title']);
        $this->assertSame('Description de la vidéo.', $videos[0]['desc']);
        $this->assertSame('https://i.ytimg.com/high.jpg', $videos[0]['thumbnail']);
        $this->assertSame('Vidéo', $videos[0]['type']);
        $this->assertSame('', $videos[0]['duration']);
    }

    public function test_sans_miniature_on_retombe_sur_img_youtube_com(): void
    {
        $items = [[
            'snippet' => [
                'title' => 'Sans miniature',
                'description' => '',
                'resourceId' => ['videoId' => 'xyz'],
            ],
        ]];

        $videos = Youtube::parseItems($items);

        $this->assertSame('https://img.youtube.com/vi/xyz/hqdefault.jpg', $videos[0]['thumbnail']);
    }

    public function test_sans_cle_api_le_service_renvoie_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\expect('set_transient')
            ->once()
            ->with(Youtube::TRANSIENT, Youtube::MOCK_VIDEOS, Youtube::TTL_ECHEC)
            ->andReturn(true);

        $videos = (new Youtube('', ''))->videos();

        $this->assertSame(Youtube::MOCK_VIDEOS, $videos);
    }

    public function test_une_erreur_http_retombe_sur_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('wp_remote_get')->justReturn('erreur');
        Functions\when('error_log')->justReturn(true);
        Functions\expect('set_transient')
            ->once()
            ->with(Youtube::TRANSIENT, Youtube::MOCK_VIDEOS, Youtube::TTL_ECHEC)
            ->andReturn(true);

        $videos = (new Youtube('cle', 'UCabc'))->videos();

        $this->assertSame(Youtube::MOCK_VIDEOS, $videos);
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
            ->with(Youtube::TRANSIENT, Youtube::MOCK_VIDEOS, Youtube::TTL_ECHEC)
            ->andReturn(true);

        $videos = (new Youtube('cle', 'UCabc'))->videos();

        $this->assertSame(Youtube::MOCK_VIDEOS, $videos);
    }

    public function test_un_appel_reussi_renvoie_les_videos_et_utilise_le_ttl_normal(): void
    {
        $body = json_encode([
            'items' => [[
                'snippet' => [
                    'title' => 'Prophétie sur les nations',
                    'description' => 'Description de la vidéo.',
                    'resourceId' => ['videoId' => 'abc123'],
                    'thumbnails' => [
                        'high' => ['url' => 'https://i.ytimg.com/high.jpg'],
                        'medium' => ['url' => 'https://i.ytimg.com/medium.jpg'],
                    ],
                ],
            ]],
        ]);

        Functions\when('get_transient')->justReturn(false);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn(['body' => $body]);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\expect('set_transient')
            ->once()
            ->with(Youtube::TRANSIENT, \Mockery::type('array'), Youtube::TTL)
            ->andReturn(true);

        $videos = (new Youtube('cle', 'UCabc'))->videos();

        $this->assertCount(1, $videos);
        $this->assertSame('abc123', $videos[0]['id']);
        $this->assertSame('Prophétie sur les nations', $videos[0]['title']);
        $this->assertSame('Description de la vidéo.', $videos[0]['desc']);
        $this->assertSame('https://i.ytimg.com/high.jpg', $videos[0]['thumbnail']);
        $this->assertSame('Vidéo', $videos[0]['type']);
        $this->assertSame('', $videos[0]['duration']);
    }

    public function test_le_cache_est_servi_sans_appel_http(): void
    {
        Functions\when('get_transient')->justReturn([['title' => 'depuis le cache']]);
        Functions\expect('wp_remote_get')->never();

        $videos = (new Youtube('cle', 'UCabc'))->videos();

        $this->assertSame('depuis le cache', $videos[0]['title']);
    }
}

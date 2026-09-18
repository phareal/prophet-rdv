<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Services\Youtube;
use Roots\Acorn\View\Composer;

class Videos extends Composer
{
    protected static $views = ['sections.videos'];

    public function with(): array
    {
        return [
            'videos' => (new Youtube(
                Options::env('YOUTUBE_API_KEY'),
                Options::env('YOUTUBE_CHANNEL_ID')
            ))->videos(),
            'channelUrl' => 'https://www.youtube.com/@ProphetJeremiahNahoum',
        ];
    }
}

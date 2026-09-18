<?php

declare(strict_types=1);

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Stats extends Composer
{
    protected static $views = ['sections.stats'];

    public function with(): array
    {
        $rows = carbon_get_theme_option('stats');

        return [
            'stats' => is_array($rows) ? $rows : [],
        ];
    }
}

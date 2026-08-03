<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Rdv\Repository;
use ProphetCore\Services\Whatsapp;
use ProphetCore\Support\Date;
use Roots\Acorn\View\Composer;

class Confirmation extends Composer
{
    protected static $views = ['template-confirmation'];

    public function with(): array
    {
        $ref = isset($_GET['ref'])
            ? sanitize_text_field(wp_unslash($_GET['ref']))
            : '';

        $rdv = $ref === '' ? null : (new Repository())->findByRef($ref);

        if ($rdv === null) {
            return ['rdv' => null, 'dateFr' => '', 'waUrl' => '', 'introuvable' => true];
        }

        return [
            'rdv' => $rdv,
            'dateFr' => Date::formatFr($rdv['date']),
            'waUrl' => Whatsapp::confirmationUrl(Options::phone1(), $rdv),
            'introuvable' => false,
        ];
    }
}

<?php

declare(strict_types=1);

namespace ProphetCore\Services;

/**
 * Rend une vue Blade du thème via Acorn. Le mu-plugin ne dépend du thème
 * que par cette classe : un thème absent dégrade l'email en texte minimal
 * plutôt que de faire échouer la soumission du rendez-vous.
 */
final class BladeRenderer implements TemplateRenderer
{
    public function render(string $view, array $data): string
    {
        if (! function_exists('\\Roots\\view')) {
            error_log('[Mailer] Acorn indisponible, gabarit ' . $view . ' non rendu');

            return '';
        }

        return (string) \Roots\view($view, $data)->render();
    }
}

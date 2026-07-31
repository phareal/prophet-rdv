<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

/**
 * WordPress n'a pas de session. Ce magasin à usage unique transporte les
 * erreurs de validation et les valeurs saisies au travers de la redirection
 * POST/Redirect/GET, sans cookie.
 */
final class FlashStore
{
    public const TTL = 300;
    private const PREFIXE = 'prophet_flash_';

    public static function put(array $payload): string
    {
        $cle = wp_generate_password(20, false);
        set_transient(self::PREFIXE . $cle, $payload, self::TTL);

        return $cle;
    }

    public static function pull(string $key): ?array
    {
        $payload = get_transient(self::PREFIXE . $key);
        delete_transient(self::PREFIXE . $key);

        return is_array($payload) ? $payload : null;
    }
}

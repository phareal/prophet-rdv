<?php

declare(strict_types=1);

namespace ProphetCore;

final class Options
{
    public static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public static function phone1(): string
    {
        return self::option('rdv_phone_1') ?: self::env('PROPHET_PHONE_1');
    }

    public static function phone2(): string
    {
        return self::option('rdv_phone_2') ?: self::env('PROPHET_PHONE_2');
    }

    public static function prophetEmail(): string
    {
        return self::option('rdv_email') ?: self::env('PROPHET_EMAIL');
    }

    /** @return array<int, string> */
    public static function heures(): array
    {
        $rows = carbon_get_theme_option('rdv_heures');

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['valeur'] ?? ''),
            $rows
        )));
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function modesPaiement(): array
    {
        $rows = carbon_get_theme_option('rdv_modes_paiement');

        if (! is_array($rows)) {
            return [];
        }

        return array_map(static fn (array $row): array => [
            'value' => (string) ($row['valeur'] ?? ''),
            'label' => (string) ($row['libelle'] ?? ''),
        ], $rows);
    }

    public static function modePaiementLabel(string $value): string
    {
        foreach (self::modesPaiement() as $mode) {
            if ($mode['value'] === $value) {
                return $mode['label'];
            }
        }

        return $value;
    }

    public static function content(string $key, string $default = ''): string
    {
        return self::option($key) ?: $default;
    }

    private static function option(string $key): string
    {
        $value = carbon_get_theme_option($key);

        return is_string($value) ? $value : '';
    }
}

<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

final class Moneroo
{
    public const API = 'https://api.moneroo.io/v1';
    private const TIMEOUT = 15;

    public function __construct(private readonly string $cleSecrete)
    {
    }

    /**
     * @param array<string, mixed> $charge
     * @return array{id: string, checkout_url: string}
     */
    public function initialiser(array $charge): array
    {
        $donnees = $this->appeler(
            'POST',
            self::API . '/payments/initialize',
            $charge
        );

        if (! isset($donnees['id'], $donnees['checkout_url'])) {
            throw new MonerooException('Réponse d\'initialisation sans identifiant ni URL de paiement');
        }

        return [
            'id' => (string) $donnees['id'],
            'checkout_url' => (string) $donnees['checkout_url'],
        ];
    }

    /** @return array<string, mixed> */
    public function recuperer(string $paiementId): array
    {
        return $this->appeler('GET', self::API . '/payments/' . rawurlencode($paiementId));
    }

    /**
     * @param array<string, mixed>|null $charge
     * @return array<string, mixed>
     */
    private function appeler(string $methode, string $url, ?array $charge = null): array
    {
        if ($this->cleSecrete === '') {
            throw new MonerooException('MONEROO_SECRET_KEY absente de la configuration');
        }

        $args = [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->cleSecrete,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if ($charge !== null) {
            $args['body'] = (string) wp_json_encode($charge);
        }

        $reponse = $methode === 'POST'
            ? wp_remote_post($url, $args)
            : wp_remote_get($url, $args);

        if (is_wp_error($reponse)) {
            throw new MonerooException('Appel Moneroo injoignable');
        }

        $code = (int) wp_remote_retrieve_response_code($reponse);
        $corps = json_decode((string) wp_remote_retrieve_body($reponse), true);

        if ($code < 200 || $code >= 300) {
            // Le message de Moneroo est technique et sans donnée personnelle.
            throw new MonerooException(sprintf(
                'Moneroo a répondu %d : %s',
                $code,
                is_array($corps) ? (string) ($corps['message'] ?? '') : ''
            ));
        }

        if (! is_array($corps) || ! isset($corps['data']) || ! is_array($corps['data'])) {
            throw new MonerooException('Réponse Moneroo inattendue');
        }

        return $corps['data'];
    }
}

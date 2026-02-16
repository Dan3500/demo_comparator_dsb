<?php

namespace App\Provider;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * provider-a funcitionality for quote (JSON).
 */
class ProviderAJson extends AbstractProvider
{
    private const URL = 'http://comparador_seguro_coches.test/provider-a/quote';

    public function getName(): string
    {
        return 'provider-a';
    }

    public function fetchQuote(int $driverAge, string $carType, string $carUse): float
    {
        return $this->doRequest($driverAge, $carType, $carUse);
    }

    protected function getUrl(): string
    {
        return self::URL;
    }

    protected function buildRequestOptions(int $driverAge, string $carType, string $carUse): array
    {
        // Map car types to Provider A format (compacto/turismo → compact)
        $carFormMap = [
            'compacto' => 'compact',
            'turismo' => 'compact',
            'suv' => 'suv',
        ];

        // Map car use to Provider A format (comercial → commercial)
        $carUseMap = [
            'comercial' => 'commercial',
            'privado' => 'private',
        ];

        return [
            'json' => [
                'driver_age' => $driverAge,
                'car_form' => $carFormMap[$carType] ?? $carType,
                'car_use' => $carUseMap[$carUse] ?? $carUse,
            ],
        ];
    }

    protected function parseResponse(ResponseInterface $response): float
    {
        $data = $response->toArray();
        return (float) preg_replace('/[^0-9.]/', '', $data['price'] ?? '0');
    }
}

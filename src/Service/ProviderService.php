<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class ProviderService
{
    private const REQUEST_TIMEOUT = 10.0;
    private const CAMPAIGN_DISCOUNT = 0.05; 

    /**
     * Provider configurations.
     * To add a new provider, simply add a new entry to this array.
     * URLs are built dynamically from the PROVIDER_BASE_URL env variable.
     */
    private array $providers;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly bool $campaignActive = false,
    ) {
        $baseUrl = rtrim($_ENV['PROVIDER_BASE_URL'] ?? $_SERVER['PROVIDER_BASE_URL'] ?? '', '/');
        $this->providers = [
            'provider-a' => [
                'url' => $baseUrl . '/provider-a/quote',
                'format' => 'json'
            ],
            'provider-b' => [
                'url' => $baseUrl . '/provider-b/quote',
                'format' => 'xml'
            ],
        ];
    }

    /**
     * Check if campaign is active.
     */
    public function isCampaignActive(): bool
    {
        return $this->campaignActive;
    }

    /**
     * Fetch quotes from request object.
     * Entry point called by the controller.
     *
     * @param Request $request The HTTP request containing driver and car data
     *
     * @return array{quotes: array, errors: array}
     */
    public function fetchQuotes(Request $request): array
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (!isset($data['driver_birthday'], $data['car_type'], $data['car_use'])) {
            throw new \InvalidArgumentException('Missing required fields: driver_birthday, car_type, car_use');
        }

        $driverBirthday = $data['driver_birthday'];
        $carType = strtolower($data['car_type']);
        $carUse = strtolower($data['car_use']);

        $validCarTypes = ['turismo', 'suv', 'compacto'];
        if (!in_array($carType, $validCarTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid car_type: "%s". Valid values: %s', $data['car_type'], implode(', ', $validCarTypes))
            );
        }

        $validCarUses = ['privado', 'comercial', 'commercial', 'private'];
        if (!in_array($carUse, $validCarUses, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid car_use: "%s". Valid values: privado, comercial', $data['car_use'])
            );
        }

        // Normalize car_use to Spanish
        if ($carUse === 'commercial') {
            $carUse = 'comercial';
        } elseif ($carUse === 'private') {
            $carUse = 'privado';
        }

        $driverAge = $this->calculateAge($driverBirthday);

        $this->logger->info('Quote request received', [
            'driver_age' => $driverAge,
            'car_type' => $carType,
            'car_use' => $carUse,
            'campaign_active' => $this->campaignActive,
        ]);

        if ($driverAge < 18) {
            $this->logger->warning('Quote rejected: driver underage', ['age' => $driverAge]);
            throw new \InvalidArgumentException('Driver must be at least 18 years old');
        }

        return $this->getFromProviders($driverAge, $carType, $carUse);
    }


    private function calculateAge(?string $birthday): int
    {
        if (!$birthday) {
            throw new \InvalidArgumentException('Missing driver_birthday field');
        }

        $birthDate = new \DateTime($birthday);
        $today = new \DateTime();

        if ($birthDate > $today) {
            throw new \InvalidArgumentException('driver_birthday cannot be in the future');
        }

        $age = $today->diff($birthDate)->y;

        return $age;
    }

    /**
     * Fetch quotes from all providers and return normalized results.
     *
     * @param int    $driverAge The driver's age
     * @param string $carType   The car type (turismo, suv, compacto)
     * @param string $carUse    The car use (privado, comercial)
     *
     * @return array{quotes: array, errors: array}
     */
    public function getFromProviders(int $driverAge, string $carType, string $carUse): array
    {
        $quotes = [];
        $errors = [];
        $responses = [];

        foreach ($this->providers as $providerName => $config) {
            $this->logger->debug('Calling provider', [
                'provider' => $providerName,
                'url' => $config['url'],
                'format' => $config['format'],
            ]);

            $responses[$providerName] = [
                'response' => $this->makeRequest($config, $driverAge, $carType, $carUse),
                'config' => $config,
                'start_time' => microtime(true),
            ];
        }

        foreach ($responses as $providerName => $data) {
            $startTime = $data['start_time'];
            $config = $data['config'];
            
            try {
                $price = $this->getResponsePrice($data['response'], $config);
                $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);

                $this->logger->info('Provider responded successfully', [
                    'provider' => $providerName,
                    'price' => $price,
                    'response_time_ms' => $elapsedMs,
                ]);

                if ($elapsedMs > 5000) {
                    $this->logger->warning('Provider response slow', [
                        'provider' => $providerName,
                        'response_time_ms' => $elapsedMs,
                    ]);
                }

                $quotes[] = [
                    'provider' => $providerName,
                    'price' => $price,
                    'currency' => 'EUR',
                    'is_cheapest' => false,
                ];
            } catch (\Exception $e) {
                $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);

                $this->logger->error('Provider failed', [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                    'response_time_ms' => $elapsedMs,
                ]);

                $errors[] = [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($this->campaignActive) {
            foreach ($quotes as &$quote) {
                $quote['original_price'] = $quote['price'];
                $quote['discounted_price'] = round($quote['price'] * (1 - self::CAMPAIGN_DISCOUNT), 2);
            }
            unset($quote);
        }

        usort($quotes, function($a, $b) {
            $priceA = $this->campaignActive ? $a['discounted_price'] : $a['price'];
            $priceB = $this->campaignActive ? $b['discounted_price'] : $b['price'];
            return $priceA <=> $priceB;
        });

        if (!empty($quotes)) {
            $quotes[0]['is_cheapest'] = true;
        }

        $this->logger->info('Quote calculation complete', [
            'quotes_count' => count($quotes),
            'errors_count' => count($errors),
            'cheapest_provider' => $quotes[0]['provider'] ?? null,
            'cheapest_price' => $quotes[0]['price'] ?? null,
            'campaign_active' => $this->campaignActive,
        ]);

        return [
            'campaign_active' => $this->campaignActive,
            'discount_percentage' => $this->campaignActive ? self::CAMPAIGN_DISCOUNT * 100 : 0,
            'quotes' => $quotes, 
            'errors' => $errors
        ];
    }

    /**
     * Make HTTP request to provider (non-blocking).
     */
    private function makeRequest(array $config, int $driverAge, string $carType, string $carUse): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        $options = ['timeout' => self::REQUEST_TIMEOUT];

        if ($config['format'] === 'json') {
            $options['json'] = [
                'driver_age' => $driverAge,
                'car_form' => $carType,
                'car_use' => $carUse,
            ];
        } else {
            $options['headers'] = ['Content-Type' => 'application/xml'];
            $options['body'] = trim('<?xml version="1.0" encoding="UTF-8"?>
<SolicitudCotizacion>
    <EdadConductor>' . $driverAge . '</EdadConductor>
    <TipoCoche>' . $carType . '</TipoCoche>
    <UsoCoche>' . $carUse . '</UsoCoche>
    <ConductorOcasional>NO</ConductorOcasional>
</SolicitudCotizacion>');
        }

        return $this->httpClient->request('POST', $config['url'], $options);
    }

    /**
     * Get price from provider response (blocking).
     */
    private function getResponsePrice(\Symfony\Contracts\HttpClient\ResponseInterface $response, array $config): float
    {
        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Request timeout: provider took longer than ' . self::REQUEST_TIMEOUT . ' seconds');
        }

        if ($statusCode !== 200) {
            throw new \RuntimeException("HTTP {$statusCode}");
        }

        if ($config['format'] === 'json') {
            $data = $response->toArray();
            return (float) preg_replace('/[^0-9.]/', '', $data['price'] ?? '0');
        }

        $xml = simplexml_load_string($response->getContent());
        if ($xml === false) {
            throw new \RuntimeException('Invalid XML response');
        }

        return (float) $xml->Precio;
    }
}

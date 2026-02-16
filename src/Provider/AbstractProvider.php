<?php

namespace App\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * AbstractProvider provides common functionality for all providers, such as making HTTP requests and measuring response times.
 * Each provider implementation must define how to build the request and parse the response.
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected const TIMEOUT = 10.0;
    protected float $lastResponseTimeMs = 0;

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getLastResponseTimeMs(): float
    {
        return $this->lastResponseTimeMs;
    }

    abstract public function getName(): string;
    abstract public function fetchQuote(int $driverAge, string $carType, string $carUse): float;
    abstract protected function getUrl(): string;
    abstract protected function buildRequestOptions(int $driverAge, string $carType, string $carUse): array;
    abstract protected function parseResponse(\Symfony\Contracts\HttpClient\ResponseInterface $response): float;

    protected function doRequest(int $driverAge, string $carType, string $carUse): float
    {
        $startTime = microtime(true);
        $options = array_merge(
            ['timeout' => self::TIMEOUT],
            $this->buildRequestOptions($driverAge, $carType, $carUse)
        );

        try {
            $response = $this->httpClient->request('POST', $this->getUrl(), $options);
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            $this->lastResponseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
            throw new \RuntimeException('Request timeout: provider took longer than ' . self::TIMEOUT . ' seconds');
        }

        $this->lastResponseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($statusCode !== 200) {
            throw new \RuntimeException("HTTP {$statusCode}");
        }

        return $this->parseResponse($response);
    }
}

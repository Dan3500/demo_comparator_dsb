<?php

namespace App\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * provider-b funcitionality for quote (XML).
 */
class ProviderBXml extends AbstractProvider
{
    private string $baseUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $providerBaseUrl
    ) {
        parent::__construct($httpClient, $logger);
        $this->baseUrl = rtrim($providerBaseUrl, '/');
    }

    public function getName(): string
    {
        return 'provider-b';
    }

    public function fetchQuote(int $driverAge, string $carType, string $carUse): float
    {
        return $this->doRequest($driverAge, $carType, $carUse);
    }

    protected function getUrl(): string
    {
        return $this->baseUrl . '/provider-b/quote';
    }

    protected function buildRequestOptions(int $driverAge, string $carType, string $carUse): array
    {
        $xml = trim('<?xml version="1.0" encoding="UTF-8"?>
<SolicitudCotizacion>
    <EdadConductor>' . $driverAge . '</EdadConductor>
    <TipoCoche>' . $carType . '</TipoCoche>
    <UsoCoche>' . $carUse . '</UsoCoche>
    <ConductorOcasional>NO</ConductorOcasional>
</SolicitudCotizacion>');

        return [
            'headers' => ['Content-Type' => 'application/xml'],
            'body' => $xml,
        ];
    }

    protected function parseResponse(ResponseInterface $response): float
    {
        $xml = simplexml_load_string($response->getContent());
        
        if ($xml === false) {
            throw new \RuntimeException('Invalid XML response');
        }

        return (float) $xml->Precio;
    }
}

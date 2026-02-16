<?php

namespace App\Provider;

/**
 * Interface for insurance quote providers.
 * Implement this interface to add new providers (OCP).
 */
interface ProviderInterface
{
    /**
     * Get the provider name identifier.
     */
    public function getName(): string;

    /**
     * Fetch a quote from the provider.
     * Returns the price or throws an exception on failure.
     */
    public function fetchQuote(int $driverAge, string $carType, string $carUse): float;

    /**
     * Get the response time of the last request in milliseconds.
     */
    public function getLastResponseTimeMs(): float;
}

<?php

namespace App\Service;

use App\DTO\Request\QuoteRequestDTO;
use App\Provider\ProviderInterface;
use App\Validator\QuoteRequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class QuoteService
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly QuoteRequestValidator $validator,
        private readonly DiscountService $discountService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Fetch quotes from HTTP Request.
     */
    public function fetchQuotes(Request $request): array
    {
        $dto = $this->validator->validate($request);
        return $this->getQuotes($dto);
    }

    /**
     * Fetch quotes from DTO.
     */
    public function getQuotes(QuoteRequestDTO $request): array
    {
        $driverAge = $this->calculateAge($request->driverBirthday);

        $this->logger->info('Quote request received', [
            'driver_age' => $driverAge,
            'car_type' => $request->carType,
            'car_use' => $request->carUse,
            'campaign_active' => $this->discountService->isCampaignActive(),
        ]);

        $quotes = [];
        $errors = [];

        foreach ($this->providers as $provider) {
            $this->logger->debug('Calling provider', [
                'provider' => $provider->getName(),
            ]);

            try {
                $price = $provider->fetchQuote($driverAge, $request->carType, $request->carUse);

                $this->logger->info('Provider responded successfully', [
                    'provider' => $provider->getName(),
                    'price' => $price,
                    'response_time_ms' => $provider->getLastResponseTimeMs(),
                ]);

                $quotes[] = [
                    'provider' => $provider->getName(),
                    'price' => $price,
                    'currency' => 'EUR',
                    'is_cheapest' => false,
                ];
            } catch (\Exception $e) {
                $this->logger->error('Provider failed', [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage(),
                    'response_time_ms' => $provider->getLastResponseTimeMs(),
                ]);

                $errors[] = [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Apply discounts and sort
        $quotes = $this->discountService->applyDiscount($quotes);
        $quotes = $this->discountService->sortByPrice($quotes);

        $this->logger->info('Quote calculation complete', [
            'quotes_count' => count($quotes),
            'errors_count' => count($errors),
            'cheapest_provider' => $quotes[0]['provider'] ?? null,
        ]);

        return [
            'campaign_active' => $this->discountService->isCampaignActive(),
            'discount_percentage' => $this->discountService->getDiscountPercentage(),
            'quotes' => $quotes,
            'errors' => $errors,
        ];
    }

    private function calculateAge(string $birthday): int
    {
        $birthDate = new \DateTime($birthday);
        $today = new \DateTime();
        return $today->diff($birthDate)->y;
    }
}

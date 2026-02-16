<?php

namespace App\Service;

/**
 * Service responsible for handling discount logic based on campaign status.
 */
class DiscountService
{
    private const CAMPAIGN_DISCOUNT = 0.05; 

    public function __construct(
        private readonly bool $campaignActive = false,
    ) {
    }

    public function isCampaignActive(): bool
    {
        return $this->campaignActive;
    }

    public function getDiscountPercentage(): float
    {
        return $this->campaignActive ? self::CAMPAIGN_DISCOUNT * 100 : 0;
    }

    public function applyDiscount(array $quotes): array
    {
        if (!$this->campaignActive) {
            return $quotes;
        }

        return array_map(function (array $quote) {
            $quote['original_price'] = $quote['price'];
            $quote['discounted_price'] = round($quote['price'] * (1 - self::CAMPAIGN_DISCOUNT), 2);
            return $quote;
        }, $quotes);
    }

    public function sortByPrice(array $quotes): array
    {
        usort($quotes, function ($a, $b) {
            $priceA = $this->campaignActive ? $a['discounted_price'] : $a['price'];
            $priceB = $this->campaignActive ? $b['discounted_price'] : $b['price'];
            return $priceA <=> $priceB;
        });

        if (!empty($quotes)) {
            $quotes[0]['is_cheapest'] = true;
        }

        return $quotes;
    }
}

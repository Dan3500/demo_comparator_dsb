<?php

namespace App\DTO\Response;

class CalculateResponseDTO implements \JsonSerializable
{
    /**
     * @param QuoteDTO[] $quotes
     * @param ProviderErrorDTO[] $errors
     */
    public function __construct(
        public readonly bool $campaignActive,
        public readonly float $discountPercentage,
        public readonly array $quotes,
        public readonly array $errors,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $quotes = array_map(
            fn(array $quote) => QuoteDTO::fromArray($quote),
            $data['quotes']
        );

        $errors = array_map(
            fn(array $error) => ProviderErrorDTO::fromArray($error),
            $data['errors']
        );

        return new self(
            campaignActive: $data['campaign_active'],
            discountPercentage: $data['discount_percentage'],
            quotes: $quotes,
            errors: $errors,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'campaign_active' => $this->campaignActive,
            'discount_percentage' => $this->discountPercentage,
            'quotes' => array_map(fn($q) => $q->jsonSerialize(), $this->quotes),
            'errors' => array_map(fn($e) => $e->jsonSerialize(), $this->errors),
        ];
    }
}

<?php

namespace App\DTO\Response;

class QuoteDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $provider,
        public readonly float $price,
        public readonly string $currency,
        public readonly bool $isCheapest,
        public readonly ?float $originalPrice = null,
        public readonly ?float $discountedPrice = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            price: $data['price'],
            currency: $data['currency'],
            isCheapest: $data['is_cheapest'],
            originalPrice: $data['original_price'] ?? null,
            discountedPrice: $data['discounted_price'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        $result = [
            'provider' => $this->provider,
            'price' => $this->price,
            'currency' => $this->currency,
            'is_cheapest' => $this->isCheapest,
        ];

        if ($this->originalPrice !== null) {
            $result['original_price'] = $this->originalPrice;
            $result['discounted_price'] = $this->discountedPrice;
        }

        return $result;
    }
}

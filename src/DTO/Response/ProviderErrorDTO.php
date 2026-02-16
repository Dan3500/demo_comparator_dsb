<?php

namespace App\DTO\Response;

class ProviderErrorDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $provider,
        public readonly string $error,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            error: $data['error'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider,
            'error' => $this->error,
        ];
    }
}

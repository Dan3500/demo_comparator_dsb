<?php

namespace App\DTO\Request;

class QuoteRequestDTO
{
    public function __construct(
        public readonly string $driverBirthday,
        public readonly string $carType,
        public readonly string $carUse,
    ) {
    }
}

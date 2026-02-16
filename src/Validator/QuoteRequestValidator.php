<?php

namespace App\Validator;

use App\DTO\Request\QuoteRequestDTO;
use Symfony\Component\HttpFoundation\Request;

class QuoteRequestValidator
{
    private const VALID_CAR_TYPES = ['turismo', 'suv', 'compacto'];
    private const VALID_CAR_USES = ['privado', 'comercial', 'commercial', 'private'];

    /**
     * Validates and transforms HTTP Request to QuoteRequestDTO.
     */
    public function validate(Request $request): QuoteRequestDTO
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->validateRequiredFields($data);
        
        $carType = $this->validateCarType($data['car_type']);
        $carUse = $this->validateCarUse($data['car_use']);
        $driverBirthday = $this->validateBirthday($data['driver_birthday']);

        return new QuoteRequestDTO(
            driverBirthday: $driverBirthday,
            carType: $carType,
            carUse: $carUse,
        );
    }

    private function validateRequiredFields(array $data): void
    {
        if (!isset($data['driver_birthday'], $data['car_type'], $data['car_use'])) {
            throw new \InvalidArgumentException('Missing required fields: driver_birthday, car_type, car_use');
        }
    }

    private function validateCarType(string $carType): string
    {
        $carType = strtolower($carType);
        
        if (!in_array($carType, self::VALID_CAR_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid car_type: "%s". Valid values: %s', $carType, implode(', ', self::VALID_CAR_TYPES))
            );
        }

        return $carType;
    }

    private function validateCarUse(string $carUse): string
    {
        $carUse = strtolower($carUse);

        if (!in_array($carUse, self::VALID_CAR_USES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid car_use: "%s". Valid values: privado, comercial', $carUse)
            );
        }

        // Normalize to Spanish
        return match ($carUse) {
            'commercial' => 'comercial',
            'private' => 'privado',
            default => $carUse,
        };
    }

    private function validateBirthday(string $birthday): string
    {
        $birthDate = new \DateTime($birthday);
        $today = new \DateTime();

        if ($birthDate > $today) {
            throw new \InvalidArgumentException('driver_birthday cannot be in the future');
        }

        $age = $today->diff($birthDate)->y;

        if ($age < 18) {
            throw new \InvalidArgumentException('Driver must be at least 18 years old');
        }

        return $birthday;
    }
}

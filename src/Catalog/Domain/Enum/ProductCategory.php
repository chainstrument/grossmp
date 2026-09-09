<?php

namespace App\Catalog\Domain\Enum;

enum ProductCategory: string
{
    case FOOD = 'food';
    case BEVERAGES = 'beverages';
    case HOUSEHOLD = 'household';
    case HYGIENE = 'hygiene';
    case PACKAGING = 'packaging';

    public function label(): string
    {
        return match ($this) {
            self::FOOD => 'Alimentaire',
            self::BEVERAGES => 'Boissons',
            self::HOUSEHOLD => 'Entretien',
            self::HYGIENE => 'Hygiène',
            self::PACKAGING => 'Emballage',
        };
    }
}

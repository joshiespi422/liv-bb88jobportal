<?php

namespace App\Enums;

enum ComplianceFormReturnType: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::ANNUAL => 'Annual',
            self::CUSTOM => 'Custom',
        };
    }
}

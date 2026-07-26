<?php

namespace App\Enums;

enum ComplianceAgency: string
{
    case BIR = 'bir';
    // case SSS = 'sss';
    // case PHILHEALTH = 'philhealth';
    // case PAG_IBIG = 'pagibig';
    // case SEC = 'sec';
    // case DOLE = 'dole';
    // case LGU = 'lgu';
    // case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BIR => 'Bureau of Internal Revenues (BIR)',
            // self::SSS => 'SSS',
            // self::PHILHEALTH => 'PhilHealth',
            // self::PAG_IBIG => 'Pag-IBIG',
            // self::SEC => 'SEC',
            // self::DOLE => 'DOLE',
            // self::LGU => 'LGU',
            // self::OTHER => 'Other',
        };
    }
}

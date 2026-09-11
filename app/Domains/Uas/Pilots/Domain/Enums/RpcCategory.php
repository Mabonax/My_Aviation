<?php

namespace App\Domains\Uas\Pilots\Domain\Enums;

enum RpcCategory: string
{
    case Unknown = 'unknown';
    case MultiRotor = 'multi_rotor';
    case FixedWing = 'fixed_wing';
    case Helicopter = 'helicopter';
    case Hybrid = 'hybrid';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Unknown / unverified',
            self::MultiRotor => 'Multi-rotor',
            self::FixedWing => 'Fixed-wing',
            self::Helicopter => 'Helicopter',
            self::Hybrid => 'Hybrid',
            self::Other => 'Other',
        };
    }
}

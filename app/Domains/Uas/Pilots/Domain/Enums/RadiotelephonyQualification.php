<?php

namespace App\Domains\Uas\Pilots\Domain\Enums;

enum RadiotelephonyQualification: string
{
    case Unverified = 'unverified';
    case Restricted = 'restricted';
    case General = 'general';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Restricted => 'Restricted',
            self::General => 'General',
            self::NotRequired => 'Not required',
        };
    }
}

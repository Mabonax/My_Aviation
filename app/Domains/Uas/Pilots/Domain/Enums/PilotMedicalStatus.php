<?php

namespace App\Domains\Uas\Pilots\Domain\Enums;

enum PilotMedicalStatus: string
{
    case Unverified = 'unverified';
    case Valid = 'valid';
    case Expired = 'expired';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Valid => 'Valid',
            self::Expired => 'Expired',
            self::NotRequired => 'Not required',
        };
    }
}

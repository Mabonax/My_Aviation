<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Enums;

enum DatasetMode: string
{
    case Unknown = 'unknown';
    case FullSnapshot = 'full_snapshot';
    case Incremental = 'incremental';
}

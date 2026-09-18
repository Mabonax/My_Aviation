<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\DTOs;

use App\Domains\Uas\AeronauticalInformation\Domain\Enums\DatasetMode;
use Carbon\CarbonImmutable;

final readonly class ProviderDataset
{
    // Transport metadata is private. No inferred snapshot completeness or receipt-time freshness.
    public function __construct(
        public array $records,
        public CarbonImmutable $timestamp,
        public array $coverage = [],
        public DatasetMode $mode = DatasetMode::Unknown,
        public array $metadata = [],
    ) {}
}

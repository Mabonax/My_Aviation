<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Contracts;

use Illuminate\Support\Collection;

interface AeronauticalRepositoryInterface
{
    public function lockDataset(): void;

    public function datasetHash(): string;

    public function advanceDataset(): int;

    public function currentItems(): Collection;

    public function storeRevision(array $source, array $interpretation): array;
}

<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Contracts;

use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderCapabilities;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderDataset;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\SourceClassification;

interface AeronauticalInformationProviderInterface
{
    public function capabilities(): ProviderCapabilities;

    public function key(): string;

    public function classification(): SourceClassification;

    public function usableForRelease(): bool;

    public function fetch(ProviderRequest $request): ProviderDataset;
}

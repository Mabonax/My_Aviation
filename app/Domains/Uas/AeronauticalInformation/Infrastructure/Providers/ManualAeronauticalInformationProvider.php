<?php

namespace App\Domains\Uas\AeronauticalInformation\Infrastructure\Providers;

use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderCapabilities;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderDataset;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\InformationType;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\SourceClassification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManualAeronauticalInformationProvider implements AeronauticalInformationProviderInterface
{
    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            providerId: $this->key(), providerName: 'Reference import: '.$this->key(), authority: $this->classification(),
            informationTypes: array_column(InformationType::cases(), 'value'),
            supportsCancellation: true, supportsReplacement: true, supportsDatasetTimestamp: true,
        );
    }

    public function key(): string
    {
        return 'manual';
    }

    public function classification(): SourceClassification
    {
        return SourceClassification::ImportedReference;
    }

    public function usableForRelease(): bool
    {
        return false;
    }

    public function fetch(ProviderRequest $request): ProviderDataset
    {
        $payload = $request->payload;
        if ($payload === null) {
            // Local operator-supplied JSON only. Never fetch a user-supplied URL.
            if (! $request->path || ! is_file($request->path) || ! is_readable($request->path) || filesize($request->path) > 20_000_000) {
                throw ValidationException::withMessages(['path' => 'Supply a readable local JSON file smaller than 20 MB.']);
            }
            $payload = json_decode(file_get_contents($request->path), true, 512, JSON_THROW_ON_ERROR);
        }
        Validator::make($payload, ['records' => 'present|array|max:10000', 'dataset_timestamp' => 'required|date'])->validate();

        // A reference import cannot assert operational coverage, even if its file claims it.
        return new ProviderDataset($payload['records'], CarbonImmutable::parse($payload['dataset_timestamp']), []);
    }
}

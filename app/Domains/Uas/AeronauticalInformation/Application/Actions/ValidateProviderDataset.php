<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderDataset;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\DatasetMode;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Domains\Uas\AeronauticalInformation\Domain\Services\ProviderPayloadSafety;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ValidateProviderDataset
{
    public function execute(ProviderDataset $dataset, AeronauticalInformationProviderInterface $provider): array
    {
        $cap = $provider->capabilities();
        if ($dataset->timestamp->isFuture() || count($dataset->records) > 10000
            || ($dataset->mode !== DatasetMode::Unknown && ! in_array($dataset->mode->value, $cap->datasetModes, true))) {
            throw ValidationException::withMessages(['dataset' => 'Dataset time, size or mode does not conform to provider capabilities.']);
        }
        app(ProviderPayloadSafety::class)->assertSafe($dataset->coverage);
        $coverage = Validator::make($dataset->coverage, [
            'complete' => 'sometimes|boolean', 'information_types' => 'sometimes|array',
            'information_types.*' => ['string', Rule::in($cap->informationTypes)],
            'bbox' => 'sometimes|array|size:4', 'bbox.*' => 'numeric',
            'valid_from' => 'sometimes|required|date', 'valid_until' => 'sometimes|required|date|after_or_equal:valid_from',
        ])->validate();
        $metadata = Validator::make($dataset->metadata, [
            'dataset_sequence' => 'sometimes|integer|min:0',
            'provider_cursor' => 'sometimes|nullable|string|max:4096',
            'previous_cursor' => 'sometimes|nullable|string|max:4096',
            'source_transaction_id' => 'sometimes|nullable|string|max:255',
            'snapshot_id' => 'sometimes|nullable|string|max:255',
            'publication_revision' => 'sometimes|nullable|string|max:255',
        ])->validate();
        if ($dataset->mode !== DatasetMode::FullSnapshot && $coverage !== []) {
            $coverage['complete'] = false;
        }
        foreach ($dataset->records as $record) {
            if (! is_array($record) || ! in_array($record['information_type'] ?? null, $cap->informationTypes, true)) {
                throw ValidationException::withMessages(['records' => 'Record type is malformed or unsupported by this provider.']);
            }
            if ((! $cap->supportsCancellation && ($record['normalized']['status'] ?? null) === 'cancelled')
                || (! $cap->supportsReplacement && ! empty($record['normalized']['replaces_identifier']))) {
                throw ValidationException::withMessages(['records' => 'Record lifecycle operation is unsupported by this provider.']);
            }
        }

        return [$coverage, $metadata];
    }

    public function assertOrder(ProviderDataset $dataset, array $metadata, ?ProviderSync $last, ProviderRequest $request): void
    {
        $previous = $last?->sync_metadata ?? [];
        if ($last?->dataset_timestamp?->gt($dataset->timestamp)
            || (isset($previous['dataset_sequence']) && (! isset($metadata['dataset_sequence']) || $metadata['dataset_sequence'] <= $previous['dataset_sequence']))) {
            throw ValidationException::withMessages(['dataset' => 'Dataset timestamp or sequence regressed or is ambiguous.']);
        }
        if ($dataset->mode === DatasetMode::Incremental) {
            if (! $last || empty($previous['provider_cursor']) || empty($metadata['provider_cursor'])
                || ($metadata['previous_cursor'] ?? null) !== $previous['provider_cursor']
                || ($request->cursor !== null && $request->cursor !== $previous['provider_cursor'])
                || $metadata['provider_cursor'] === $previous['provider_cursor']) {
                throw ValidationException::withMessages(['dataset' => 'Incremental continuity is not established. Request a full snapshot.']);
            }
        }
        foreach (['source_transaction_id', 'snapshot_id'] as $identity) {
            if (! empty($metadata[$identity]) && $metadata[$identity] === ($previous[$identity] ?? null)) {
                // An identical dataset was already handled before reaching this check.
                throw ValidationException::withMessages(['dataset' => 'A dataset identifier was reused for different content.']);
            }
        }
    }
}

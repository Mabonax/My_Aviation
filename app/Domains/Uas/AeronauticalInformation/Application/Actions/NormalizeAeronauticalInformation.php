<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Application\ProviderRegistry;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\InformationType;
use App\Domains\Uas\AeronauticalInformation\Domain\Services\ProviderPayloadSafety;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NormalizeAeronauticalInformation
{
    public function execute(array $record, AeronauticalInformationProviderInterface $provider): array
    {
        app(ProviderPayloadSafety::class)->assertSafe($record);
        Validator::make($record, [
            'source_identifier' => 'required|string|max:160', 'source_revision' => 'required|string|max:160',
            'information_type' => ['required', Rule::enum(InformationType::class)], 'title' => 'required|string|max:255',
            'source_url' => 'nullable|url:http,https|max:2048', 'raw_message' => 'nullable|string|max:200000',
            'issued_at' => 'nullable|date', 'effective_from' => 'nullable|date', 'effective_until' => 'nullable|date',
            'normalized' => 'sometimes|array', 'normalized.status' => 'sometimes|in:active,cancelled',
            'normalized.geometry_json' => 'nullable|array', 'normalized.permanent' => 'sometimes|boolean',
            'normalized.latitude' => 'nullable|numeric|between:-90,90', 'normalized.longitude' => 'nullable|numeric|between:-180,180',
            'normalized.radius_nm' => 'nullable|numeric|min:0|max:2000',
            'normalized.lower_limit_value' => 'nullable|numeric|min:0', 'normalized.upper_limit_value' => 'nullable|numeric|min:0',
            'normalized.lower_limit_unit' => 'nullable|in:FT,M,FL', 'normalized.upper_limit_unit' => 'nullable|in:FT,M,FL',
            'normalized.lower_limit_reference' => 'nullable|in:AGL,AMSL,FL,SFC', 'normalized.upper_limit_reference' => 'nullable|in:AGL,AMSL,FL,UNL',
            'normalized.fir_code' => 'nullable|string|max:8', 'normalized.aerodrome_code' => 'nullable|string|max:8',
            'normalized.country_code' => 'nullable|string|size:2', 'normalized.hazard' => 'sometimes|in:restriction,warning,information,unknown',
            'normalized.replaces_identifier' => 'nullable|string|max:160',
        ])->validate();
        if ($provider->usableForRelease() && ! in_array($provider->classification()->value, ['official_live', 'official_publication'], true)) {
            throw ValidationException::withMessages(['provider' => 'This source classification cannot establish operational authority.']);
        }
        if (! empty($record['effective_from']) && ! empty($record['effective_until'])
            && CarbonImmutable::parse($record['effective_until'])->lt(CarbonImmutable::parse($record['effective_from']))) {
            throw ValidationException::withMessages(['effective_until' => 'End cannot precede the effective start.']);
        }
        $limits = $record['normalized'] ?? [];
        if (isset($limits['lower_limit_value'], $limits['upper_limit_value'])
            && ($limits['lower_limit_unit'] ?? null) === ($limits['upper_limit_unit'] ?? null)
            && ($limits['lower_limit_reference'] ?? null) === ($limits['upper_limit_reference'] ?? null)
            && $limits['lower_limit_value'] > $limits['upper_limit_value']) {
            throw ValidationException::withMessages(['normalized' => 'Lower altitude cannot exceed the upper altitude.']);
        }
        $rawHash = hash('sha256', json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $identity = hash('sha256', $provider->key().'|'.$record['source_identifier'].'|'.$record['source_revision'].'|'.$rawHash);
        $provenance = [
            'provider' => $provider->key(), 'source_classification' => $provider->classification()->value,
            'source_identifier' => $record['source_identifier'], 'source_revision' => $record['source_revision'],
            'checksum' => $rawHash, 'received_at' => now(),
            'issued_at' => empty($record['issued_at']) ? null : CarbonImmutable::parse($record['issued_at'])->utc(), 'effective_from' => empty($record['effective_from']) ? null : CarbonImmutable::parse($record['effective_from'])->utc(), 'effective_until' => empty($record['effective_until']) ? null : CarbonImmutable::parse($record['effective_until'])->utc(),
        ];
        $normalized = $record['normalized'] ?? [];
        // Partial Q-line parsing is descriptive only. It never infers a release prohibition from prose.
        if ($record['information_type'] === 'NOTAM' && preg_match('/Q\)\s*([A-Z]{4})\/([A-Z]{5})\/([^\/\r\n]*)\/([^\/\r\n]*)\/([^\/\r\n]*)\/([0-9]{3})\/([0-9]{3})\//', $record['raw_message'] ?? '', $q)) {
            $normalized += ['fir_code' => $q[1], 'q_code' => $q[2], 'traffic' => $q[3], 'purpose' => $q[4], 'scope' => $q[5], 'q_lower_flight_level' => $q[6], 'q_upper_flight_level' => $q[7]];
        }
        $normalized['hazard'] ??= 'unknown';
        $normalized['parser_version'] = 'YAW-AIM-NORMALIZE-1.0';

        return [
            'source' => [...$provenance, 'source_url' => $record['source_url'] ?? null, 'raw_message' => $record['raw_message'] ?? null, 'raw_payload' => $record, 'identity_hash' => $identity],
            'item' => [
                ...$provenance,
                ...Arr::only($normalized, ['summary', 'normalized_text', 'lower_limit_value', 'lower_limit_unit', 'lower_limit_reference', 'upper_limit_value', 'upper_limit_unit', 'upper_limit_reference', 'geometry_type', 'latitude', 'longitude', 'radius_nm', 'geometry_json', 'fir_code', 'aerodrome_code', 'country_code']),
                'information_type' => $record['information_type'], 'title' => $record['title'], 'status' => $normalized['status'] ?? 'active',
                'usable_for_release' => app(ProviderRegistry::class)->operationallyApproved($provider), 'permanent' => $normalized['permanent'] ?? false,
                'interpretation' => $normalized, 'identity_hash' => hash('sha256', $identity.'|'.$normalized['parser_version']),
            ],
        ];
    }
}

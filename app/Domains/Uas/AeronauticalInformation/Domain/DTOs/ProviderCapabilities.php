<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\DTOs;

use App\Domains\Uas\AeronauticalInformation\Domain\Enums\SourceClassification;

final readonly class ProviderCapabilities
{
    /** @param list<string> $informationTypes @param list<string> $coverageTypes @param list<string> $datasetModes */
    public function __construct(
        public string $providerId,
        public string $providerName,
        public SourceClassification $authority,
        public bool $operational = false,
        public array $informationTypes = [],
        public array $coverageTypes = [],
        public array $datasetModes = [],
        public bool $supportsCancellation = false,
        public bool $supportsReplacement = false,
        public bool $supportsDatasetTimestamp = false,
        public bool $supportsGeometry = false,
        public bool $supportsAltitude = false,
        public bool $supportsFir = false,
        public bool $supportsAerodrome = false,
        public bool $supportsAixm = false,
    ) {}

    public function toArray(): array
    {
        return [
            'provider_id' => $this->providerId, 'provider_name' => $this->providerName,
            'authority' => $this->authority->value, 'operational' => $this->operational,
            'information_types' => $this->informationTypes, 'coverage_types' => $this->coverageTypes,
            'dataset_modes' => $this->datasetModes, 'supports_cancellation' => $this->supportsCancellation,
            'supports_replacement' => $this->supportsReplacement, 'supports_dataset_timestamp' => $this->supportsDatasetTimestamp,
            'supports_geometry' => $this->supportsGeometry, 'supports_altitude' => $this->supportsAltitude,
            'supports_fir' => $this->supportsFir, 'supports_aerodrome' => $this->supportsAerodrome,
            'supports_aixm' => $this->supportsAixm,
            'supports_notam' => in_array('NOTAM', $this->informationTypes, true),
            'supports_pib' => in_array('PIB', $this->informationTypes, true),
            'supports_met' => array_intersect(['METAR', 'TAF', 'SIGMET'], $this->informationTypes) !== [],
            'supports_full_snapshot' => in_array('full_snapshot', $this->datasetModes, true),
            'supports_incremental_sync' => in_array('incremental', $this->datasetModes, true),
        ];
    }
}

<?php

namespace App\Domains\Uas\AeronauticalInformation\Infrastructure\Providers;

use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderDataset;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\SourceClassification;
use Illuminate\Validation\ValidationException;

class SacaaPublicationProvider extends ManualAeronauticalInformationProvider
{
    public function key(): string
    {
        return 'sacaa_publications';
    }

    public function classification(): SourceClassification
    {
        return SourceClassification::OfficialPublication;
    }

    public function fetch(ProviderRequest $request): ProviderDataset
    {
        $dataset = parent::fetch($request);
        foreach ($dataset->records as $record) {
            $url = parse_url($record['source_url'] ?? '');
            $host = strtolower($url['host'] ?? '');
            if (! in_array($record['information_type'] ?? '', ['AIP', 'AIP_AMENDMENT', 'AIP_SUPPLEMENT', 'AIC', 'AIRAC'], true)
                || ($url['scheme'] ?? '') !== 'https'
                || ! ($host === 'caa.co.za' || str_ends_with($host, '.caa.co.za'))) {
                throw ValidationException::withMessages(['records' => 'Publication references require a supported publication type and an HTTPS SACAA URL.']);
            }
        }

        return $dataset;
    }
}

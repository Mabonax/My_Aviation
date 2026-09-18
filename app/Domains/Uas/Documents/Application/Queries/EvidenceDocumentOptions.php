<?php

namespace App\Domains\Uas\Documents\Application\Queries;

use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;

class EvidenceDocumentOptions
{
    public function execute(User $user): array
    {
        return [
            'operators' => app(CurrentOperatorContext::class)->scopeOperatorsFor($user)
                ->orderBy('legal_entity')
                ->get(['id', 'legal_entity'])
                ->map(fn ($operator): array => ['id' => $operator->id, 'label' => $operator->legal_entity])
                ->values()
                ->all(),
            'categories' => [
                'operator_certificate' => 'Operator certificate',
                'aircraft_registration' => 'Aircraft registration',
                'mission_authorisation' => 'Mission authorisation',
                'training_record' => 'Training record',
                'compliance_finding' => 'Compliance finding',
                'gis_output' => 'GIS output',
                'authority_correspondence' => 'Authority correspondence',
                'other' => 'Other',
            ],
            'access_levels' => [
                'operator' => 'Operator',
                'internal' => 'Internal',
                'authority_submission' => 'Authority submission',
            ],
        ];
    }
}

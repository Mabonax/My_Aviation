<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;

class ManualRevisionDistributionReport
{
    public function execute(UasOperationsManualRevision $manualRevision): array
    {
        $distributions = $manualRevision->distributions()->orderBy('recipient_role')->orderBy('recipient_name')->get()->map(fn ($distribution): array => [
            'id' => $distribution->id,
            'recipient_name' => $distribution->recipient_name,
            'recipient_role' => $distribution->recipient_role,
            'recipient_email' => $distribution->recipient_email,
            'distribution_channel' => $distribution->distribution_channel,
            'distribution_status' => $distribution->distribution_status,
            'acknowledgement_status' => $distribution->acknowledgement_status ?? 'pending',
            'required_by' => $distribution->required_by?->toDateString(),
            'distributed_at' => $distribution->distributed_at?->toDateTimeString(),
            'acknowledged_at' => $distribution->acknowledged_at?->toDateTimeString(),
            'acknowledgement_statement' => $distribution->acknowledgement_statement,
            'acknowledgement_notes' => $distribution->acknowledgement_notes,
            'evidence_references' => $distribution->evidence_references ?? [],
            'notes' => $distribution->notes,
        ])->values()->all();

        return [
            'summary' => [
                'total' => count($distributions),
                'required' => collect($distributions)->where('distribution_status', 'required')->count(),
                'distributed' => collect($distributions)->where('distribution_status', 'distributed')->count(),
                'waived' => collect($distributions)->where('distribution_status', 'waived')->count(),
                'acknowledgement_pending' => collect($distributions)->where('acknowledgement_status', 'pending')->count(),
                'acknowledged' => collect($distributions)->where('acknowledgement_status', 'acknowledged')->count(),
            ],
            'distributions' => $distributions,
        ];
    }
}

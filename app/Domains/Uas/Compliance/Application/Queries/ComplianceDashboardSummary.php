<?php

namespace App\Domains\Uas\Compliance\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Documents\Domain\Models\RegulatoryDocument;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;

class ComplianceDashboardSummary
{
    public function execute(): array
    {
        return [
            'pilots' => UasPilot::query()->count(),
            'aircraft' => UasAircraft::query()->count(),
            'certificates_expiring_30_days' => PilotCertificate::query()
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
            'open_findings' => ComplianceFinding::query()->whereNull('resolved_at')->count(),
            'documents_missing_review' => RegulatoryDocument::query()->where('status', 'draft')->count(),
        ];
    }
}

<?php

namespace App\Domains\Uas\Compliance\Http\Controllers;

use App\Domains\Uas\Compliance\Application\Queries\ComplianceTraceabilityReport;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceTraceabilityController extends Controller
{
    public function index(ComplianceTraceabilityReport $report): Response
    {
        Gate::authorize('viewAny', RegulatoryRequirement::class);

        return Inertia::render('compliance/traceability', [
            'report' => $report->execute(),
        ]);
    }
}

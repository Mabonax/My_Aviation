<?php

namespace App\Domains\Uas\Compliance\Http\Controllers;

use App\Domains\Uas\Compliance\Application\Queries\ComplianceRegisterReport;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceRegisterController extends Controller
{
    public function index(ComplianceRegisterReport $report): Response
    {
        Gate::authorize('viewAny', RegulatoryRequirement::class);

        return Inertia::render('compliance/register', [
            'report' => $report->execute(),
        ]);
    }
}

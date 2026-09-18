<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Queries\ApplicationRenewalPackReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationRenewalPackController extends Controller
{
    public function show(UasOperatorCertificateCase $certificateCase, ApplicationRenewalPackReport $report): Response
    {
        Gate::authorize('view', $certificateCase->operator);

        return Inertia::render('operators/certificate-cases/application-pack', [
            'pack' => $report->execute($certificateCase),
        ]);
    }
}

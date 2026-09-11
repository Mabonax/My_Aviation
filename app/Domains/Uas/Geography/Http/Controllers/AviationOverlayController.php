<?php

namespace App\Domains\Uas\Geography\Http\Controllers;

use App\Domains\Uas\Geography\Application\Queries\AviationOverlayReport;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AviationOverlayController extends Controller
{
    public function index(AviationOverlayReport $report): Response
    {
        return Inertia::render('geography/overlays', [
            'report' => $report->execute(),
        ]);
    }
}

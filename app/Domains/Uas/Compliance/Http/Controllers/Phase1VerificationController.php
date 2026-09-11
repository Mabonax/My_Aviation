<?php

namespace App\Domains\Uas\Compliance\Http\Controllers;

use App\Domains\Uas\Compliance\Application\Queries\Phase1VerificationReport;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Phase1VerificationController extends Controller
{
    public function index(Phase1VerificationReport $report): Response
    {
        return Inertia::render('phase1/verification', [
            'report' => $report->execute(),
        ]);
    }

    public function export(Phase1VerificationReport $report): StreamedResponse
    {
        $data = $report->execute();

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Requirement', 'Name', 'Status', 'Evidence']);

            foreach ($data['requirements'] as $requirement) {
                fputcsv($handle, [
                    $requirement['code'],
                    $requirement['name'],
                    $requirement['status'],
                    $requirement['evidence'],
                ]);
            }

            fclose($handle);
        }, 'phase-1-verification.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}

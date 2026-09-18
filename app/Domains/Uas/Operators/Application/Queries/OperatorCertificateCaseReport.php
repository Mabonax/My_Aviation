<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;

class OperatorCertificateCaseReport
{
    public function execute(UasOperator $operator): array
    {
        $cases = $operator->certificateCases()
            ->with('opener')
            ->latest('deadline_at')
            ->get()
            ->map(fn (UasOperatorCertificateCase $case): array => [
                'id' => $case->id,
                'case_number' => $case->case_number,
                'case_type' => $case->case_type,
                'status' => $case->status,
                'deadline_at' => $case->deadline_at?->toDateString(),
                'outstanding_documents_count' => count($case->outstanding_documents ?? []),
                'submission_status' => $case->submission_status,
                'outcome' => $case->outcome,
                'opened_by' => $case->opener?->name,
            ])->values()->all();

        return [
            'summary' => [
                'total' => count($cases),
                'open' => collect($cases)->whereNotIn('status', ['approved', 'rejected', 'withdrawn'])->count(),
                'ready' => collect($cases)->where('submission_status', 'ready')->count(),
                'submitted' => collect($cases)->where('submission_status', 'submitted')->count(),
            ],
            'cases' => $cases,
        ];
    }
}
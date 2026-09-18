<?php

namespace App\Domains\Uas\Operators\Domain\Services;

class CertificateCaseLifecycle
{
    public const TYPES = [
        'application' => 'Application',
        'amendment' => 'Amendment',
        'renewal' => 'Renewal',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'evidence_collection' => 'Evidence Collection',
        'ready_for_submission' => 'Ready for Submission',
        'submitted' => 'Submitted',
        'authority_review' => 'Authority Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
    ];

    public function submissionStatus(array $data): string
    {
        $status = $data['status'] ?? 'draft';
        $outstanding = array_filter($data['outstanding_documents'] ?? []);

        return match (true) {
            in_array($status, ['submitted', 'authority_review'], true) => 'submitted',
            $status === 'approved' => 'approved',
            $status === 'rejected' => 'rejected',
            $status === 'withdrawn' => 'withdrawn',
            count($outstanding) === 0 && in_array($status, ['ready_for_submission', 'evidence_collection'], true) => 'ready',
            default => 'not_ready',
        };
    }
}
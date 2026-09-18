<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Operators\Domain\Services\CertificateCaseLifecycle;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCertificateCase
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-002; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'UASOC/ROC application, amendment and renewal case management with evidence, submission, authority correspondence and outcome tracking.',
    ];

    public function __construct(private readonly CertificateCaseLifecycle $lifecycle, private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperator $operator, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperatorCertificateCase
    {
        return DB::transaction(function () use ($operator, $data, $actor, $ipAddress, $userAgent): UasOperatorCertificateCase {
            $payload = [
                ...$data,
                'uas_operator_id' => $operator->id,
                'opened_by' => $actor->id,
                'updated_by' => $actor->id,
                'case_number' => $data['case_number'] ?? $this->nextCaseNumber($data['case_type']),
                'status' => $data['status'] ?? 'draft',
                'evidence_requirements' => $data['evidence_requirements'] ?? [],
                'outstanding_documents' => $data['outstanding_documents'] ?? [],
                'fleet_scope' => $data['fleet_scope'] ?? [],
                'personnel_scope' => $data['personnel_scope'] ?? [],
                'ops_spec_scope' => $data['ops_spec_scope'] ?? [],
                'fees' => $data['fees'] ?? [],
                'authority_correspondence' => $data['authority_correspondence'] ?? [],
                ...self::TRACEABILITY,
            ];
            $payload['submission_status'] = $this->lifecycle->submissionStatus($payload);

            $certificateCase = UasOperatorCertificateCase::query()->create($payload);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $certificateCase, 'operator.certificate_case.created', 'FR-OPS-002', self::TRACEABILITY['regulatory_source'], null, $certificateCase->getAttributes(), $ipAddress, $userAgent));

            return $certificateCase;
        });
    }

    private function nextCaseNumber(string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $next = UasOperatorCertificateCase::query()->lockForUpdate()->count() + 1;

        return "OPS-{$prefix}-".now()->format('Ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
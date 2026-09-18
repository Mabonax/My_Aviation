<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Operators\Domain\Services\CertificateCaseLifecycle;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateCertificateCase
{
    public function __construct(private readonly CertificateCaseLifecycle $lifecycle, private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperatorCertificateCase $certificateCase, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperatorCertificateCase
    {
        return DB::transaction(function () use ($certificateCase, $data, $actor, $ipAddress, $userAgent): UasOperatorCertificateCase {
            $previous = $certificateCase->getAttributes();
            $payload = [
                ...$data,
                'updated_by' => $actor->id,
                'evidence_requirements' => $data['evidence_requirements'] ?? [],
                'outstanding_documents' => $data['outstanding_documents'] ?? [],
                'fleet_scope' => $data['fleet_scope'] ?? [],
                'personnel_scope' => $data['personnel_scope'] ?? [],
                'ops_spec_scope' => $data['ops_spec_scope'] ?? [],
                'fees' => $data['fees'] ?? [],
                'authority_correspondence' => $data['authority_correspondence'] ?? [],
            ];
            $payload['submission_status'] = $this->lifecycle->submissionStatus($payload);

            $certificateCase->fill($payload)->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $certificateCase, 'operator.certificate_case.updated', 'FR-OPS-002', $certificateCase->regulatory_source, $previous, $certificateCase->getAttributes(), $ipAddress, $userAgent));

            return $certificateCase->refresh();
        });
    }
}
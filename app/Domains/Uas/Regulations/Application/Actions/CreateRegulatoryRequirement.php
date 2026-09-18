<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRegulatoryRequirement
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryRequirement
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): RegulatoryRequirement {
            $requirement = RegulatoryRequirement::query()->create([
                ...$this->attributes($data),
                'status' => $data['status'] ?? 'active',
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $requirement, 'regulatory_requirement.created', 'FR-REG-001', $requirement->official_source, null, $requirement->getAttributes(), $ipAddress, $userAgent));

            return $requirement;
        });
    }

    private function attributes(array $data): array
    {
        return [
            'requirement_id' => $data['requirement_id'],
            'regulation_part' => $data['regulation_part'],
            'clause_reference' => $data['clause_reference'] ?? null,
            'title' => $data['title'],
            'requirement_text' => $data['requirement_text'],
            'responsible_party' => $data['responsible_party'],
            'applicability' => $data['applicability'],
            'system_control' => $data['system_control'],
            'evidence_required' => $data['evidence_required'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'validity_period' => $data['validity_period'] ?? null,
            'retention_period' => $data['retention_period'] ?? null,
            'effective_date' => $data['effective_date'],
            'superseded_date' => $data['superseded_date'] ?? null,
            'official_source' => $data['official_source'],
            'source_version' => $data['source_version'],
        ];
    }
}

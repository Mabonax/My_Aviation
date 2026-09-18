<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupersedeRegulatoryRequirement
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(RegulatoryRequirement $previous, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryRequirement
    {
        return DB::transaction(function () use ($previous, $data, $actor, $ipAddress, $userAgent): RegulatoryRequirement {
            $previousValues = $previous->getAttributes();
            $previous->update([
                'status' => 'superseded',
                'superseded_date' => $data['effective_date'],
            ]);

            $next = RegulatoryRequirement::query()->create([
                'previous_requirement_id' => $previous->id,
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
                'official_source' => $data['official_source'],
                'source_version' => $data['source_version'],
                'status' => 'active',
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $previous, 'regulatory_requirement.superseded', 'FR-REG-002', $previous->official_source, $previousValues, $previous->getAttributes(), $ipAddress, $userAgent));
            $this->recordAuditEntry->execute(new AuditEntryData($actor, $next, 'regulatory_requirement.version_created', 'FR-REG-002', $next->official_source, null, $next->getAttributes(), $ipAddress, $userAgent));

            return $next;
        });
    }
}

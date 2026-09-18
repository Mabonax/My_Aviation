<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateManualRevision
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperationsManualRevision $revision, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperationsManualRevision
    {
        return DB::transaction(function () use ($revision, $data, $actor, $ipAddress, $userAgent): UasOperationsManualRevision {
            $previous = $revision->getAttributes();

            $revision->fill([
                ...$data,
                'sections' => $data['sections'] ?? [],
                'evidence_references' => $data['evidence_references'] ?? [],
                'updated_by' => $actor->id,
            ])->save();

            if ($revision->superseded_revision_id) {
                UasOperationsManualRevision::query()->whereKey($revision->superseded_revision_id)->update(['approval_status' => 'superseded']);
            }

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $revision, 'operator.manual_revision.updated', 'FR-OM-001', $revision->regulatory_source, $previous, $revision->getAttributes(), $ipAddress, $userAgent));

            return $revision->refresh();
        });
    }
}
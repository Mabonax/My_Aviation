<?php

namespace App\Domains\Uas\Geography\Application\Actions;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Services\GisProjectLifecycle;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionGisProjectLifecycle
{
    public function __construct(
        private readonly GisProjectLifecycle $lifecycle,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UasGisProject $project, string $nextState, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasGisProject
    {
        if (! $this->lifecycle->canTransition($project->lifecycle_state, $nextState)) {
            throw ValidationException::withMessages(['lifecycle_state' => 'The GIS project lifecycle transition is not allowed.']);
        }

        return DB::transaction(function () use ($project, $nextState, $actor, $ipAddress, $userAgent): UasGisProject {
            $previous = $project->getAttributes();

            $project->forceFill([
                'lifecycle_state' => $nextState,
                'updated_by' => $actor->id,
            ])->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $project, 'gis_project.lifecycle_transitioned', 'FR-GIS-001', 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41', $previous, $project->getAttributes(), $ipAddress, $userAgent));

            return $project;
        });
    }
}

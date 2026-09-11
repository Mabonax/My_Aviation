<?php

namespace App\Domains\Uas\Checklists\Application\Actions;

use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Checklists\Domain\Services\ChecklistCompletionEvaluator;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordMissionChecklist
{
    public function __construct(
        private readonly ChecklistCompletionEvaluator $evaluator,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UasMission $mission, string $type, array $results, ?string $exceptions, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasMissionChecklist
    {
        return DB::transaction(function () use ($mission, $type, $results, $exceptions, $actor, $ipAddress, $userAgent): UasMissionChecklist {
            $template = UasChecklistTemplate::query()
                ->where('type', $type)
                ->where('active', true)
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->first();

            if (! $template) {
                throw ValidationException::withMessages([
                    'checklist' => "No active {$type} checklist template is configured.",
                ]);
            }

            $normalised = $this->evaluator->normalise($template, $results);
            $state = $this->evaluator->state($template, $normalised, $exceptions);

            $checklist = UasMissionChecklist::query()->create([
                'uas_mission_id' => $mission->id,
                'uas_checklist_template_id' => $template->id,
                'performed_by' => $actor->id,
                'type' => $type,
                'checklist_version' => $template->version,
                'performed_at' => now(),
                'results' => $normalised,
                'exceptions' => $exceptions,
                'state' => $state,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $checklist,
                action: "mission_checklist.{$type}.recorded",
                requirementId: $type === 'pre_flight' ? 'FR-CHK-001' : 'FR-CHK-002',
                regulatorySource: $template->regulatory_source,
                previousValues: null,
                newValues: $checklist->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $checklist;
        });
    }
}
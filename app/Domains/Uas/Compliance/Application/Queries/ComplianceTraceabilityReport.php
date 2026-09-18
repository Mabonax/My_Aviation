<?php

namespace App\Domains\Uas\Compliance\Application\Queries;

use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Training\Domain\Models\UasTrainingComplianceLink;

class ComplianceTraceabilityReport
{
    public function execute(): array
    {
        $findings = ComplianceFinding::query()->with('regulatoryRequirement')->latest('id')->get();
        $notifications = ComplianceNotification::query()->with('regulatoryRequirement')->latest('id')->get();
        $audits = UasAuditEntry::query()->with('regulatoryRequirement')->latest('id')->limit(50)->get();
        $trainingLinks = UasTrainingComplianceLink::query()->with(['regulatoryRequirement', 'course'])->latest('id')->get();

        $controls = [
            ...$findings->map(fn (ComplianceFinding $finding): array => $this->row(
                'finding',
                $finding->id,
                $finding->requirement_id,
                $finding->summary,
                $finding->state,
                $finding->regulatoryRequirement,
            ))->all(),
            ...$notifications->map(fn (ComplianceNotification $notification): array => $this->row(
                'notification',
                $notification->id,
                $notification->requirement_id,
                $notification->subject,
                $notification->status,
                $notification->regulatoryRequirement,
            ))->all(),
            ...$audits->map(fn (UasAuditEntry $audit): array => $this->row(
                'audit',
                $audit->id,
                $audit->requirement_id,
                $audit->action,
                'recorded',
                $audit->regulatoryRequirement,
                $audit->regulatory_source,
            ))->all(),
            ...$trainingLinks->map(fn (UasTrainingComplianceLink $link): array => $this->row(
                'training',
                $link->id,
                $link->requirement_reference,
                $link->course ? "{$link->course->code} / {$link->title}" : $link->title,
                $link->link_status,
                $link->regulatoryRequirement,
                $link->regulatory_source,
            ))->all(),
        ];

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_controls' => count($controls),
                'traceable_controls' => collect($controls)->where('traceability_status', 'traceable')->count(),
                'source_text_only_controls' => collect($controls)->where('traceability_status', 'source_text_only')->count(),
                'missing_source_controls' => collect($controls)->where('traceability_status', 'missing_source')->count(),
            ],
            'controls' => $controls,
        ];
    }

    private function row(string $controlType, int $id, ?string $requirementId, string $control, string $state, ?RegulatoryRequirement $requirement, ?string $fallbackSource = null): array
    {
        $source = $requirement?->official_source ?: $fallbackSource;
        $sourceVersion = $requirement?->source_version;

        return [
            'id' => $id,
            'control_type' => $controlType,
            'requirement_id' => $requirementId,
            'control' => $control,
            'state' => $state,
            'traceability_status' => $requirement ? 'traceable' : ($source ? 'source_text_only' : 'missing_source'),
            'regulatory_source' => $source,
            'source_version' => $sourceVersion,
            'responsible_party' => $requirement?->responsible_party,
            'applicability' => $requirement?->applicability,
            'evidence_required' => $requirement?->evidence_required,
            'effective_date' => $requirement?->effective_date?->toDateString(),
            'requirement_status' => $requirement?->status,
        ];
    }
}

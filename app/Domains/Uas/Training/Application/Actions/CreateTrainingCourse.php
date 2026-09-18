<?php

namespace App\Domains\Uas\Training\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTrainingCourse
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-TRN-001; Training & Competency domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Training course learning structure with modules, lessons, resources, assessments, competencies and competency records while separating regulated/ATO, operator-internal and general education content.',
    ];

    private const LINK_TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-TRN-002; Training & Competency domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Regulatory or organisational requirements linked to a required competency and retained training/competency record.',
    ];

    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasTrainingCourse
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasTrainingCourse {
            $course = UasTrainingCourse::query()->create([
                'code' => $data['code'],
                'title' => $data['title'],
                'classification' => $data['classification'],
                'status' => $data['status'] ?? 'draft',
                'summary' => $data['summary'] ?? null,
                'authority_approval_reference' => $data['authority_approval_reference'] ?? null,
                ...self::TRACEABILITY,
                'created_by' => $actor->id,
            ]);

            $modules = $this->normaliseLines($data['modules'] ?? []);
            $lessons = $this->normaliseLines($data['lessons'] ?? []);
            $resources = $this->normaliseLines($data['resources'] ?? []);
            $assessments = $this->normaliseLines($data['assessments'] ?? []);
            $competencies = $this->normaliseLines($data['competencies'] ?? []);
            $records = $this->normaliseLines($data['competency_records'] ?? []);
            $complianceLinks = [
                ...$this->normaliseComplianceLinks($data['compliance_links'] ?? []),
                ...$this->normaliseRegulatoryRequirementLinks($data['regulatory_requirement_ids'] ?? []),
            ];

            if ($modules === []) {
                $modules = ['Course Foundation'];
            }

            $firstModule = null;
            foreach ($modules as $index => $title) {
                $module = $course->modules()->create(['title' => $title, 'sequence' => $index + 1]);
                $firstModule ??= $module;
            }

            if ($lessons === []) {
                $lessons = ['Course Overview'];
            }

            $firstLesson = null;
            foreach ($lessons as $index => $title) {
                $lesson = $firstModule->lessons()->create(['title' => $title, 'sequence' => $index + 1, 'lesson_type' => 'lesson']);
                $firstLesson ??= $lesson;
            }

            foreach ($resources as $title) {
                $firstLesson->resources()->create(['title' => $title, 'resource_type' => 'document']);
            }

            foreach ($assessments as $title) {
                $course->assessments()->create(['title' => $title, 'assessment_type' => 'assessment']);
            }

            $firstCompetency = null;
            foreach ($competencies as $title) {
                $competency = $course->competencies()->create(['title' => $title, 'standard' => $title]);
                $firstCompetency ??= $competency;
            }

            $firstRecord = null;
            foreach ($records as $participant) {
                $record = $course->competencyRecords()->create(['participant_name' => $participant, 'competency_title' => $competencies[0] ?? $course->title, 'record_status' => 'planned', 'evidence_references' => []]);
                $firstRecord ??= $record;
            }

            foreach ($complianceLinks as $link) {
                $course->complianceLinks()->create([
                    'training_competency_id' => $firstCompetency?->id,
                    'training_competency_record_id' => $firstRecord?->id,
                    ...$link,
                    ...self::LINK_TRACEABILITY,
                ]);
            }

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $course, 'training.course.created', 'FR-TRN-001', self::TRACEABILITY['regulatory_source'], null, $course->getAttributes(), $ipAddress, $userAgent));

            if ($complianceLinks !== []) {
                $this->recordAuditEntry->execute(new AuditEntryData($actor, $course, 'training.compliance_link.created', 'FR-TRN-002', self::LINK_TRACEABILITY['regulatory_source'], null, ['links' => $complianceLinks], $ipAddress, $userAgent));
            }

            return $course->refresh();
        });
    }

    private function normaliseLines(array $items): array
    {
        return collect($items)->map(fn ($item) => trim((string) $item))->filter()->values()->all();
    }

    private function normaliseComplianceLinks(array $items): array
    {
        return collect($items)->map(function ($item): array {
            $title = trim((string) $item);

            return [
                'source_type' => 'organisational_requirement',
                'requirement_reference' => $title,
                'title' => $title,
                'responsible_role' => 'Training Manager',
                'applicability' => 'Applies where the linked course competency is required by an organisational or regulatory control.',
                'evidence_required' => 'Linked competency record with retained training evidence.',
                'link_status' => 'required',
            ];
        })->filter(fn (array $item): bool => $item['title'] !== '')->values()->all();
    }

    private function normaliseRegulatoryRequirementLinks(array $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return RegulatoryRequirement::query()
            ->whereIn('id', $ids)
            ->orderBy('requirement_id')
            ->get()
            ->map(fn (RegulatoryRequirement $requirement): array => [
                'regulatory_requirement_id' => $requirement->id,
                'source_type' => 'regulatory_requirement',
                'requirement_reference' => $requirement->requirement_id,
                'title' => $requirement->title,
                'responsible_role' => $requirement->responsible_party,
                'applicability' => $requirement->applicability,
                'evidence_required' => $requirement->evidence_required ?: $requirement->system_control,
                'validity_period' => $requirement->validity_period,
                'retention_period' => $requirement->retention_period,
                'link_status' => 'required',
            ])
            ->all();
    }
}

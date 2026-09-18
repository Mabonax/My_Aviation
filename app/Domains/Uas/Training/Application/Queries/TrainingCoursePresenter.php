<?php

namespace App\Domains\Uas\Training\Application\Queries;

use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;

class TrainingCoursePresenter
{
    public static function toArray(UasTrainingCourse $course): array
    {
        $course->loadMissing(['modules.lessons.resources', 'assessments', 'competencies', 'competencyRecords', 'complianceLinks.competency', 'complianceLinks.competencyRecord']);

        return [
            'id' => $course->id,
            'code' => $course->code,
            'title' => $course->title,
            'classification' => $course->classification,
            'status' => $course->status,
            'summary' => $course->summary,
            'authority_approval_reference' => $course->authority_approval_reference,
            'modules' => $course->modules->map(fn ($module): array => [
                'id' => $module->id,
                'title' => $module->title,
                'sequence' => $module->sequence,
                'lessons' => $module->lessons->map(fn ($lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'sequence' => $lesson->sequence,
                    'lesson_type' => $lesson->lesson_type,
                    'resources' => $lesson->resources->map(fn ($resource): array => ['id' => $resource->id, 'title' => $resource->title, 'resource_type' => $resource->resource_type, 'reference' => $resource->reference])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
            'assessments' => $course->assessments->map(fn ($assessment): array => ['id' => $assessment->id, 'title' => $assessment->title, 'assessment_type' => $assessment->assessment_type, 'pass_mark' => $assessment->pass_mark])->values()->all(),
            'competencies' => $course->competencies->map(fn ($competency): array => ['id' => $competency->id, 'title' => $competency->title, 'standard' => $competency->standard])->values()->all(),
            'competency_records' => $course->competencyRecords->map(fn ($record): array => ['id' => $record->id, 'participant_name' => $record->participant_name, 'competency_title' => $record->competency_title, 'record_status' => $record->record_status])->values()->all(),
            'compliance_links' => $course->complianceLinks->map(fn ($link): array => [
                'id' => $link->id,
                'source_type' => $link->source_type,
                'requirement_reference' => $link->requirement_reference,
                'title' => $link->title,
                'responsible_role' => $link->responsible_role,
                'applicability' => $link->applicability,
                'evidence_required' => $link->evidence_required,
                'validity_period' => $link->validity_period,
                'retention_period' => $link->retention_period,
                'due_date' => $link->due_date?->toDateString(),
                'link_status' => $link->link_status,
                'competency_title' => $link->competency?->title,
                'competency_record' => $link->competencyRecord ? "{$link->competencyRecord->participant_name} / {$link->competencyRecord->record_status}" : null,
            ])->values()->all(),
            'regulatory_source' => $course->regulatory_source,
            'regulatory_source_version' => $course->regulatory_source_version,
            'regulatory_effective_date' => $course->regulatory_effective_date?->toDateString(),
        ];
    }
}

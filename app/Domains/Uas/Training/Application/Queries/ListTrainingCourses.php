<?php

namespace App\Domains\Uas\Training\Application\Queries;

use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;

class ListTrainingCourses
{
    public function execute(): array
    {
        return UasTrainingCourse::query()->withCount(['modules', 'assessments', 'competencies', 'competencyRecords', 'complianceLinks'])->orderBy('code')->get()->map(fn ($course): array => [
            'id' => $course->id,
            'code' => $course->code,
            'title' => $course->title,
            'classification' => $course->classification,
            'status' => $course->status,
            'modules_count' => $course->modules_count,
            'assessments_count' => $course->assessments_count,
            'competencies_count' => $course->competencies_count,
            'records_count' => $course->competency_records_count,
            'compliance_links_count' => $course->compliance_links_count,
        ])->all();
    }
}

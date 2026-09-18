<?php

namespace App\Domains\Uas\Training\Http\Requests;

use App\Domains\Uas\Training\Domain\Services\TrainingClassification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreTrainingCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Domains\Uas\Training\Domain\Models\UasTrainingCourse::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80', 'unique:uas_training_courses,code'],
            'title' => ['required', 'string', 'max:180'],
            'classification' => ['required', 'string', Rule::in(array_keys(TrainingClassification::CLASSIFICATIONS))],
            'status' => ['required', 'string', Rule::in(array_keys(TrainingClassification::STATUSES))],
            'summary' => ['nullable', 'string', 'max:5000'],
            'authority_approval_reference' => ['nullable', 'string', 'max:180', 'required_if:classification,regulated_ato'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'max:180'],
            'lessons' => ['nullable', 'array'],
            'lessons.*' => ['string', 'max:180'],
            'resources' => ['nullable', 'array'],
            'resources.*' => ['string', 'max:240'],
            'assessments' => ['nullable', 'array'],
            'assessments.*' => ['string', 'max:180'],
            'competencies' => ['nullable', 'array'],
            'competencies.*' => ['string', 'max:180'],
            'competency_records' => ['nullable', 'array'],
            'competency_records.*' => ['string', 'max:180'],
            'compliance_links' => ['nullable', 'array'],
            'compliance_links.*' => ['string', 'max:180'],
            'regulatory_requirement_ids' => ['nullable', 'array'],
            'regulatory_requirement_ids.*' => ['integer', 'exists:regulatory_requirements,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $links = collect($this->input('compliance_links', []))->filter(fn ($item): bool => trim((string) $item) !== '');
                $regulatoryRequirements = collect($this->input('regulatory_requirement_ids', []))->filter();

                if ($links->isEmpty() && $regulatoryRequirements->isEmpty()) {
                    return;
                }

                if (collect($this->input('competencies', []))->filter()->isEmpty()) {
                    $validator->errors()->add('competencies', 'At least one competency is required when compliance links are captured.');
                }

                if (collect($this->input('competency_records', []))->filter()->isEmpty()) {
                    $validator->errors()->add('competency_records', 'At least one competency record is required when compliance links are captured.');
                }
            },
        ];
    }
}

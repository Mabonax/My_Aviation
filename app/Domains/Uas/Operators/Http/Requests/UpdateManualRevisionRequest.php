<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Domain\Services\OperationsManualControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManualRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('manualRevision')->operator) ?? false;
    }

    public function rules(): array
    {
        $revision = $this->route('manualRevision');

        return [
            'manual_name' => ['required', 'string', 'max:180'],
            'revision_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('uas_operations_manual_revisions', 'revision_code')
                    ->where(fn ($query) => $query
                        ->where('uas_operator_id', $revision->uas_operator_id)
                        ->where('manual_name', $this->input('manual_name')))
                    ->ignore($revision),
            ],
            'effective_date' => ['nullable', 'date'],
            'approval_status' => ['required', 'string', Rule::in(array_keys(OperationsManualControl::APPROVAL_STATUSES))],
            'authority_approval_reference' => ['nullable', 'string', 'max:160'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'max:240'],
            'change_summary' => ['nullable', 'string', 'max:5000'],
            'superseded_revision_id' => [
                'nullable',
                'integer',
                Rule::exists('uas_operations_manual_revisions', 'id')
                    ->where(fn ($query) => $query
                        ->where('uas_operator_id', $revision->uas_operator_id)
                        ->where('id', '!=', $revision->id)),
            ],
            'evidence_references' => ['nullable', 'array'],
            'evidence_references.*' => ['string', 'max:500'],
        ];
    }
}

<?php

namespace App\Domains\Uas\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvidenceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'],
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['active', 'draft', 'archived'])],
            'uas_operator_id' => ['nullable', 'integer', 'exists:uas_operators,id'],
            'evidenceable_type' => ['nullable', Rule::in(['operator', 'aircraft', 'mission', 'compliance_finding'])],
            'evidenceable_id' => ['nullable', 'integer'],
            'evidence_role' => ['nullable', 'string', 'max:80'],
            'requirement_id' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'version' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'access_level' => ['nullable', Rule::in(['operator', 'internal', 'authority_submission'])],
            'source_reference' => ['nullable', 'string', 'max:180'],
            'effective_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'retention_ends_at' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegulatoryFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RegulatoryForm::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'form_code' => ['required', 'string', 'max:80'],
            'form_title' => ['required', 'string', 'max:255'],
            'regulatory_area' => ['required', 'string', 'max:120'],
            'revision' => ['required', 'string', 'max:80', Rule::unique('regulatory_forms', 'revision')->where(fn (Builder $query) => $query->where('form_code', $this->input('form_code')))],
            'effective_date' => ['required', 'date'],
            'superseded_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'source_reference' => ['required', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'required_transaction' => ['required', 'string', 'max:180'],
            'status' => ['required', 'string', Rule::in(['draft', 'active', 'inactive'])],
            'verified_at' => ['nullable', 'date'],
        ];
    }
}

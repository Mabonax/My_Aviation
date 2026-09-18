<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupersedeRegulatoryFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('regulatoryForm');

        return $form instanceof RegulatoryForm
            && ($this->user()?->can('update', $form) ?? false);
    }

    public function rules(): array
    {
        $form = $this->route('regulatoryForm');
        $effectiveFrom = $form instanceof RegulatoryForm ? $form->effective_date?->toDateString() : null;

        return [
            'form_code' => ['required', 'string', 'max:80'],
            'form_title' => ['required', 'string', 'max:255'],
            'regulatory_area' => ['required', 'string', 'max:120'],
            'revision' => ['required', 'string', 'max:80', Rule::unique('regulatory_forms', 'revision')->where(fn (Builder $query) => $query->where('form_code', $this->input('form_code')))],
            'effective_date' => array_filter(['required', 'date', $effectiveFrom ? 'after_or_equal:'.$effectiveFrom : null]),
            'source_reference' => ['required', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'required_transaction' => ['required', 'string', 'max:180'],
            'verified_at' => ['nullable', 'date'],
        ];
    }
}

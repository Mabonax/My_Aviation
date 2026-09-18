<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegulatoryFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RegulatoryFee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'regulation_part' => ['required', 'string', 'max:120'],
            'transaction_code' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source' => ['required', 'string', 'max:255'],
            'source_version' => ['required', 'string', 'max:120', Rule::unique('regulatory_fees', 'source_version')->where(fn (Builder $query) => $query->where('transaction_code', $this->input('transaction_code')))],
            'status' => ['required', 'string', Rule::in(['draft', 'active', 'inactive'])],
            'verified_at' => ['nullable', 'date'],
        ];
    }
}

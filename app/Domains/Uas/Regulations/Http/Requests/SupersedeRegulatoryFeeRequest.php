<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupersedeRegulatoryFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fee = $this->route('regulatoryFee');

        return $fee instanceof RegulatoryFee
            && ($this->user()?->can('update', $fee) ?? false);
    }

    public function rules(): array
    {
        $fee = $this->route('regulatoryFee');
        $effectiveFrom = $fee instanceof RegulatoryFee ? $fee->effective_from?->toDateString() : null;

        return [
            'regulation_part' => ['required', 'string', 'max:120'],
            'transaction_code' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_from' => array_filter(['required', 'date', $effectiveFrom ? 'after:'.$effectiveFrom : null]),
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source' => ['required', 'string', 'max:255'],
            'source_version' => ['required', 'string', 'max:120', Rule::unique('regulatory_fees', 'source_version')->where(fn (Builder $query) => $query->where('transaction_code', $this->input('transaction_code')))],
            'verified_at' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperatorMembershipStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $membership instanceof UasOperatorMembership
            && ($this->user()?->can('manageMemberships', $membership->operator) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(UasOperatorMembership::statuses()))],
        ];
    }
}

<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatorMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMemberships', $this->route('operator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'membership_role' => ['required', 'string', Rule::in(array_keys(UasOperatorMembership::roles()))],
            'status' => ['required', 'string', Rule::in(array_keys(UasOperatorMembership::statuses()))],
        ];
    }
}

<?php

namespace App\Domains\Uas\Pilots\Http\Requests;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Foundation\Http\FormRequest;

class LinkPilotProfileUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('linkUser', $this->route('pilot')) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:uas_pilots,user_id'],
        ];
    }
}

<?php

namespace App\Domains\Uas\Geography\Http\Requests;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Services\GisProjectLifecycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionGisProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('gisProject');

        return $project instanceof UasGisProject && ($this->user()?->can('update', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'lifecycle_state' => ['required', 'string', Rule::in(array_keys(GisProjectLifecycle::STATES))],
        ];
    }
}

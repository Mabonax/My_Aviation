<?php

namespace App\Domains\Uas\AeronauticalInformation\Http\Requests;

use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use Illuminate\Foundation\Http\FormRequest;

class ImportReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', AeronauticalInformationItem::class) && $this->user()->can('sync', AeronauticalInformationItem::class);
    }

    public function rules(): array
    {
        return ['provider' => 'required|in:manual,sacaa_publications,fixture', 'file' => 'required|file|max:19531'];
    }
}

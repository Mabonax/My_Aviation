<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;

class RegulatoryFormPresenter
{
    public static function toArray(RegulatoryForm $form): array
    {
        $form->loadMissing(['previousForm', 'supersedingForms']);

        return [
            'id' => $form->id,
            'previous_form_id' => $form->previous_form_id,
            'previous_form' => $form->previousForm ? self::summary($form->previousForm) : null,
            'superseding_forms' => $form->supersedingForms->map(fn (RegulatoryForm $version): array => self::summary($version))->values()->all(),
            'form_code' => $form->form_code,
            'form_title' => $form->form_title,
            'regulatory_area' => $form->regulatory_area,
            'revision' => $form->revision,
            'effective_date' => $form->effective_date?->toDateString(),
            'superseded_date' => $form->superseded_date?->toDateString(),
            'source_reference' => $form->source_reference,
            'source_url' => $form->source_url,
            'required_transaction' => $form->required_transaction,
            'status' => $form->status,
            'verified_at' => $form->verified_at?->toISOString(),
        ];
    }

    private static function summary(RegulatoryForm $form): array
    {
        return [
            'id' => $form->id,
            'form_code' => $form->form_code,
            'form_title' => $form->form_title,
            'revision' => $form->revision,
            'status' => $form->status,
            'effective_date' => $form->effective_date?->toDateString(),
        ];
    }
}

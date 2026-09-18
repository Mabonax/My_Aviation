<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;

class ListRegulatoryForms
{
    public function execute(): array
    {
        return RegulatoryForm::query()
            ->withCount('supersedingForms')
            ->orderBy('regulatory_area')
            ->orderBy('form_code')
            ->orderByDesc('effective_date')
            ->get()
            ->map(fn (RegulatoryForm $form): array => [
                'id' => $form->id,
                'form_code' => $form->form_code,
                'form_title' => $form->form_title,
                'regulatory_area' => $form->regulatory_area,
                'revision' => $form->revision,
                'effective_date' => $form->effective_date?->toDateString(),
                'superseded_date' => $form->superseded_date?->toDateString(),
                'required_transaction' => $form->required_transaction,
                'status' => $form->status,
                'verified_at' => $form->verified_at?->toISOString(),
                'superseding_versions_count' => $form->superseding_forms_count,
            ])
            ->all();
    }
}

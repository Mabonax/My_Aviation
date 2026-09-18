<?php

namespace App\Domains\Uas\Aircraft\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;

class AircraftOnboardingOptions
{
    public function execute(User $user): array
    {
        return [
            'catalogue_models' => UasAircraftModel::query()
                ->with('manufacturer')
                ->orderBy('model')
                ->limit(200)
                ->get()
                ->map(fn (UasAircraftModel $model): array => [
                    'id' => $model->id,
                    'label' => $model->manufacturer->name.' '.$model->model,
                    'manufacturer' => $model->manufacturer->name,
                    'model' => $model->model,
                    'summary' => AircraftModelPresenter::toArray($model),
                ])
                ->values()
                ->all(),
            'operators' => app(CurrentOperatorContext::class)->scopeOperatorsFor($user)
                ->orderBy('legal_entity')
                ->get(['id', 'legal_entity'])
                ->map(fn ($operator): array => ['id' => $operator->id, 'label' => $operator->legal_entity])
                ->values()
                ->all(),
            'onboarding_statuses' => [
                'draft' => 'Draft',
                'onboarding' => 'Onboarding',
                'active' => 'Active',
                'grounded' => 'Grounded',
                'retired' => 'Retired',
            ],
        ];
    }
}

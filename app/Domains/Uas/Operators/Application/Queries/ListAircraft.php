<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Aircraft\Application\Queries\AircraftModelPresenter;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftReadinessSummary;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Models\User;

class ListAircraft
{
    public function __construct(private readonly AircraftReadinessSummary $readiness) {}

    public function execute(User $user): array
    {
        $operatorIds = app(CurrentOperatorContext::class)->accessibleOperatorIds($user);

        return UasAircraft::query()
            ->when(! $user->hasAnyUasPermission(['operators.view', 'missions.view']), function ($query) use ($operatorIds) {
                $query->whereHas('operators', fn ($operators) => $operators
                    ->whereIn('uas_operators.id', $operatorIds)
                    ->where('uas_operator_aircraft.status', 'active'));
            })
            ->with(['catalogueModel.manufacturer', 'operators', 'registrations', 'approvals', 'defects', 'batteries', 'components'])
            ->orderBy('registration')
            ->get()
            ->map(fn (UasAircraft $aircraft): array => [
                'id' => $aircraft->id,
                'catalogue_model' => $aircraft->catalogueModel ? AircraftModelPresenter::toArray($aircraft->catalogueModel) : null,
                'registration' => $aircraft->registration,
                'manufacturer' => $aircraft->manufacturer,
                'model' => $aircraft->model,
                'serial_number' => $aircraft->serial_number,
                'operational_status' => $aircraft->operational_status,
                'operator_names' => $aircraft->operators->pluck('legal_entity')->values()->all(),
                'readiness' => $this->readiness->execute($aircraft),
                'package_instantiation' => [
                    'state' => $aircraft->package_instantiation_state,
                    'instantiated_at' => $aircraft->package_instantiated_at?->toISOString(),
                    'battery_count' => $aircraft->batteries->whereNotNull('package_item_key')->count(),
                    'component_count' => $aircraft->components->count(),
                ],
            ])
            ->values()
            ->all();
    }
}

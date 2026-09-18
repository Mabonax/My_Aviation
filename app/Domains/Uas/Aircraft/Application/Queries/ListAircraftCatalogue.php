<?php

namespace App\Domains\Uas\Aircraft\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAircraftCatalogue
{
    public function execute(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return UasAircraftModel::query()
            ->with('manufacturer')
            ->when($filters['manufacturer'] ?? null, fn ($query, $manufacturer) => $query->whereHas('manufacturer', fn ($manufacturers) => $manufacturers
                ->where('slug', $manufacturer)
                ->orWhere('name', $manufacturer)
                ->orWhere('id', $manufacturer)))
            ->when($filters['aircraft_type'] ?? null, fn ($query, $type) => $query->where('aircraft_type', $type))
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'deprecated') {
                    $query->where('catalogue_status', 'deprecated');
                } elseif ($status === 'current') {
                    $query->where('catalogue_status', '!=', 'deprecated');
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($filters['catalogue_status'] ?? null, fn ($query, $status) => $query->where('catalogue_status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('model', 'like', "%{$search}%")
                    ->orWhere('family', 'like', "%{$search}%")
                    ->orWhere('primary_use', 'like', "%{$search}%")
                    ->orWhereHas('manufacturer', fn ($manufacturers) => $manufacturers->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('catalogue_status')
            ->join('uas_manufacturers', 'uas_manufacturers.id', '=', 'uas_aircraft_models.manufacturer_id')
            ->orderBy('uas_manufacturers.name')
            ->orderBy('uas_aircraft_models.model')
            ->select('uas_aircraft_models.*')
            ->paginate($perPage)
            ->through(fn (UasAircraftModel $model): array => AircraftModelPresenter::toArray($model));
    }
}

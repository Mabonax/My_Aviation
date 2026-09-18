<?php

namespace App\Domains\Uas\Aircraft\Http\Controllers;

use App\Domains\Uas\Aircraft\Application\Queries\AircraftCatalogueFilters;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftModelPresenter;
use App\Domains\Uas\Aircraft\Application\Queries\ListAircraftCatalogue;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AircraftCatalogueController extends Controller
{
    public function index(ListAircraftCatalogue $catalogue, AircraftCatalogueFilters $filters): Response
    {
        return Inertia::render('aircraft/catalogue/index', [
            'models' => $catalogue->execute(request()->only(['search', 'manufacturer', 'aircraft_type', 'status', 'catalogue_status'])),
            'filters' => $filters->execute(),
            'activeFilters' => request()->only(['search', 'manufacturer', 'aircraft_type', 'status', 'catalogue_status']),
        ]);
    }

    public function show(UasAircraftModel $aircraftModel): Response
    {
        return Inertia::render('aircraft/catalogue/show', [
            'model' => AircraftModelPresenter::toArray($aircraftModel),
        ]);
    }
}

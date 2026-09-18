<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftModelPresenter;
use App\Domains\Uas\Aircraft\Application\Queries\ListAircraftCatalogue;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AircraftCatalogueController extends Controller
{
    public function index(Request $request, ListAircraftCatalogue $catalogue): JsonResponse
    {
        $models = $catalogue->execute($request->only(['search', 'manufacturer', 'aircraft_type', 'status', 'catalogue_status']), 25);

        return ApiResponse::success([
            'aircraft_models' => $models->items(),
            'pagination' => [
                'current_page' => $models->currentPage(),
                'per_page' => $models->perPage(),
                'total' => $models->total(),
                'last_page' => $models->lastPage(),
            ],
        ]);
    }

    public function show(UasAircraftModel $aircraftModel): JsonResponse
    {
        return ApiResponse::success([
            'aircraft_model' => AircraftModelPresenter::toArray($aircraftModel),
        ]);
    }
}

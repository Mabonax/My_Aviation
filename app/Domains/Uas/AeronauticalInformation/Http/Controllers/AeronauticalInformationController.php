<?php

namespace App\Domains\Uas\AeronauticalInformation\Http\Controllers;

use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\AeronauticalRegister;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\AeronauticalInformation\Http\Requests\ImportReferenceRequest;
use App\Domains\Uas\AeronauticalInformation\Http\Requests\RegisterRequest;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AeronauticalInformationController extends Controller
{
    public function index(RegisterRequest $request, AeronauticalRegister $register)
    {
        $data = $register->execute($request->validated(), $request->user());

        return $request->routeIs('api.v1.*') ? ApiResponse::success($data) : Inertia::render('aeronautical-information/index', $data);
    }

    public function show(Request $request, AeronauticalInformationItem $item, AeronauticalRegister $register)
    {
        $data = $register->detail($item, $request->user());

        return $request->routeIs('api.v1.*') ? ApiResponse::success($data) : Inertia::render('aeronautical-information/show', $data);
    }

    public function store(ImportReferenceRequest $request, SyncAeronauticalInformation $sync)
    {
        $sync->execute($request->validated('provider'), new ProviderRequest(path: $request->file('file')->getRealPath()), $request->user());

        return to_route('aeronautical-information.index')->with('success', 'Reference dataset imported. Operational authority has not been established.');
    }
}

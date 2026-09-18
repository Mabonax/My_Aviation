<?php

use App\Domains\Uas\Api\Http\Controllers\V1\AircraftController;
use App\Domains\Uas\Api\Http\Controllers\V1\AircraftCatalogueController;
use App\Domains\Uas\Api\Http\Controllers\V1\AuthController;
use App\Domains\Uas\Api\Http\Controllers\V1\CurrentUserController;
use App\Domains\Uas\Api\Http\Controllers\V1\EvidenceDocumentController;
use App\Domains\Uas\Api\Http\Controllers\V1\MissionController;
use Illuminate\Support\Facades\Route;

use App\Domains\Uas\Api\Http\Controllers\V1\WorkosAuthController;
use App\Http\Middleware\ValidateWorkosApiSession;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/workos/authorize', [WorkosAuthController::class, 'authorize'])->middleware('throttle:10,1')->name('auth.workos.authorize');
    Route::post('auth/workos/exchange', [WorkosAuthController::class, 'exchange'])->middleware('throttle:10,1')->name('auth.workos.exchange');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware(['auth:sanctum', ValidateWorkosApiSession::class])->group(function () {
        Route::get('aeronautical-information', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\AeronauticalInformationController::class, 'index'])->name('aeronautical-information.index');
        Route::get('aeronautical-information/{item}', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\AeronauticalInformationController::class, 'show'])->name('aeronautical-information.show');
        Route::get('missions/{mission}/briefing', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\MissionBriefingController::class, 'show'])->name('missions.briefing.show');
        Route::post('missions/{mission}/briefing', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\MissionBriefingController::class, 'store'])->name('missions.briefing.store');
        Route::post('missions/{mission}/briefing/{briefing}/acknowledge', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\MissionBriefingController::class, 'acknowledge'])->name('missions.briefing.acknowledge');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [CurrentUserController::class, 'me'])->name('me');
        Route::get('me/pilot', [CurrentUserController::class, 'pilot'])->name('me.pilot');
        Route::get('me/operators', [CurrentUserController::class, 'operators'])->name('me.operators');
        Route::get('aircraft-catalogue', [AircraftCatalogueController::class, 'index'])->name('aircraft-catalogue.index');
        Route::get('aircraft-catalogue/{aircraftModel}', [AircraftCatalogueController::class, 'show'])->name('aircraft-catalogue.show');
        Route::get('aircraft', [AircraftController::class, 'index'])->name('aircraft.index');
        Route::get('aircraft/{aircraft}', [AircraftController::class, 'show'])->name('aircraft.show');
        Route::get('evidence-documents', [EvidenceDocumentController::class, 'index'])->name('evidence-documents.index');
        Route::post('evidence-documents', [EvidenceDocumentController::class, 'store'])->name('evidence-documents.store');
        Route::get('missions', [MissionController::class, 'index'])->name('missions.index');
        Route::get('missions/{mission}/compliance', [MissionController::class, 'compliance'])->name('missions.compliance');
        Route::get('missions/{mission}/post-flight-propagation', [MissionController::class, 'postFlightPropagation'])->name('missions.post-flight-propagation.show');
        Route::post('missions/{mission}/post-flight-propagation', [MissionController::class, 'propagatePostFlight'])->name('missions.post-flight-propagation.store');
        Route::get('missions/{mission}', [MissionController::class, 'show'])->name('missions.show');
    });
});

<?php

use App\Domains\Uas\Batteries\Http\Controllers\BatteryController;
use App\Domains\Uas\Batteries\Http\Controllers\MissionBatteryUsageController;
use App\Domains\Uas\Checklists\Http\Controllers\PostFlightChecklistController;
use App\Domains\Uas\Checklists\Http\Controllers\PreFlightChecklistController;
use App\Domains\Uas\Compliance\Application\Queries\ComplianceDashboardSummary;
use App\Domains\Uas\Crew\Http\Controllers\MissionCrewController;
use App\Domains\Uas\Defects\Http\Controllers\AircraftDefectController;
use App\Domains\Uas\Compliance\Http\Controllers\Phase1VerificationController;
use App\Domains\Uas\Geography\Http\Controllers\AviationOverlayController;
use App\Domains\Uas\Missions\Http\Controllers\MissionController;
use App\Domains\Uas\Pilots\Http\Controllers\PilotProfileController;
use App\Domains\Uas\Tracks\Http\Controllers\FlightTrackController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (ComplianceDashboardSummary $summary) {
        return Inertia::render('dashboard', [
            'summary' => $summary->execute(),
        ]);
    })->name('dashboard');

    Route::resource('pilots', PilotProfileController::class)->except(['destroy']);
    Route::resource('batteries', BatteryController::class)->only(['index', 'create', 'store']);
    Route::resource('defects', AircraftDefectController::class)->only(['index', 'create', 'store']);
    Route::resource('missions', MissionController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('missions/{mission}/pre-flight-checklist', [PreFlightChecklistController::class, 'create'])->name('missions.pre-flight-checklist.create');
    Route::post('missions/{mission}/pre-flight-checklist', [PreFlightChecklistController::class, 'store'])->name('missions.pre-flight-checklist.store');
    Route::get('missions/{mission}/post-flight-checklist', [PostFlightChecklistController::class, 'create'])->name('missions.post-flight-checklist.create');
    Route::post('missions/{mission}/post-flight-checklist', [PostFlightChecklistController::class, 'store'])->name('missions.post-flight-checklist.store');
    Route::get('missions/{mission}/crew/create', [MissionCrewController::class, 'create'])->name('missions.crew.create');
    Route::post('missions/{mission}/crew', [MissionCrewController::class, 'store'])->name('missions.crew.store');
    Route::get('missions/{mission}/tracks/create', [FlightTrackController::class, 'create'])->name('missions.tracks.create');
    Route::post('missions/{mission}/tracks', [FlightTrackController::class, 'store'])->name('missions.tracks.store');
    Route::get('missions/{mission}/batteries/create', [MissionBatteryUsageController::class, 'create'])->name('missions.batteries.create');
    Route::post('missions/{mission}/batteries', [MissionBatteryUsageController::class, 'store'])->name('missions.batteries.store');
    Route::get('missions/{mission}/defects/create', [AircraftDefectController::class, 'create'])->name('missions.defects.create');
    Route::post('missions/{mission}/defects', [AircraftDefectController::class, 'store'])->name('missions.defects.store');
    Route::get('aviation-overlays', [AviationOverlayController::class, 'index'])->name('aviation-overlays.index');
    Route::get('phase-1/verification', [Phase1VerificationController::class, 'index'])->name('phase-1.verification');
    Route::get('phase-1/verification/export', [Phase1VerificationController::class, 'export'])->name('phase-1.verification.export');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

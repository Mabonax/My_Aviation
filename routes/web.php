<?php

use App\Domains\Uas\Batteries\Http\Controllers\BatteryController;
use App\Domains\Uas\Batteries\Http\Controllers\MissionBatteryUsageController;
use App\Domains\Uas\Aircraft\Http\Controllers\AircraftController;
use App\Domains\Uas\Aircraft\Http\Controllers\AircraftCatalogueController;
use App\Domains\Uas\Checklists\Http\Controllers\PostFlightChecklistController;
use App\Domains\Uas\Checklists\Http\Controllers\PreFlightChecklistController;
use App\Domains\Uas\Compliance\Application\Queries\ComplianceDashboardSummary;
use App\Domains\Uas\Compliance\Http\Controllers\ComplianceRegisterController;
use App\Domains\Uas\Compliance\Http\Controllers\ComplianceTraceabilityController;
use App\Domains\Uas\Crew\Http\Controllers\MissionCrewController;
use App\Domains\Uas\Defects\Http\Controllers\AircraftDefectController;
use App\Domains\Uas\Documents\Http\Controllers\EvidenceDocumentController;
use App\Domains\Uas\Compliance\Http\Controllers\Phase1VerificationController;
use App\Domains\Uas\Geography\Http\Controllers\AviationOverlayController;
use App\Domains\Uas\Geography\Http\Controllers\GisDatasetController;
use App\Domains\Uas\Geography\Http\Controllers\GisFeatureController;
use App\Domains\Uas\Geography\Http\Controllers\GisProjectMissionController;
use App\Domains\Uas\Geography\Http\Controllers\GisProjectController;
use App\Domains\Uas\Missions\Http\Controllers\MissionController;
use App\Domains\Uas\Notifications\Http\Controllers\ComplianceNotificationController;
use App\Domains\Uas\Operators\Http\Controllers\OperatorCertificateCaseController;
use App\Domains\Uas\Operators\Http\Controllers\OperatorAssignmentController;
use App\Domains\Uas\Operators\Http\Controllers\OperatorMembershipController;
use App\Domains\Uas\Operators\Http\Controllers\OperatorProfileController;
use App\Domains\Uas\Operators\Http\Controllers\OperatorWorkspaceController;
use App\Domains\Uas\Operators\Http\Controllers\OperationsManualAcknowledgementController;
use App\Domains\Uas\Operators\Http\Controllers\OperationsManualDistributionController;
use App\Domains\Uas\Operators\Http\Controllers\OperationsManualRevisionController;
use App\Domains\Uas\Operators\Http\Controllers\OperationsManualTrainingRequirementController;
use App\Domains\Uas\Operators\Http\Controllers\ApplicationRenewalPackController;
use App\Domains\Uas\Pilots\Http\Controllers\PilotProfileController;
use App\Domains\Uas\Pilots\Http\Controllers\MyComplianceController;
use App\Domains\Uas\Pilots\Http\Controllers\MyPilotProfileController;
use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Application\Queries\MyPilotWorkspace;
use App\Domains\Uas\Regulations\Http\Controllers\RegulatoryExternalIntegrationController;
use App\Domains\Uas\Regulations\Http\Controllers\RegulatoryFeeController;
use App\Domains\Uas\Regulations\Http\Controllers\RegulatoryFormController;
use App\Domains\Uas\Regulations\Http\Controllers\RegulatoryRequirementController;
use App\Domains\Uas\Tracks\Http\Controllers\FlightTrackController;
use App\Domains\Uas\Training\Http\Controllers\TrainingCourseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::post('operator-workspace', [OperatorWorkspaceController::class, 'select'])->name('operator-workspace.select');
    Route::delete('operator-workspace', [OperatorWorkspaceController::class, 'clear'])->name('operator-workspace.clear');

    Route::get('aeronautical-information', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\AeronauticalInformationController::class, 'index'])->name('aeronautical-information.index');
    Route::post('aeronautical-information/import', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\AeronauticalInformationController::class, 'store'])->name('aeronautical-information.import');
    Route::get('aeronautical-information/{item}', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\AeronauticalInformationController::class, 'show'])->name('aeronautical-information.show');
    Route::get('missions/{mission}/briefing', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\MissionBriefingController::class, 'show'])->name('missions.briefing.show');
    Route::post('missions/{mission}/briefing', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\MissionBriefingController::class, 'store'])->name('missions.briefing.store');
    Route::post('missions/{mission}/briefing/{briefing}/acknowledge', [\App\Domains\Uas\AeronauticalInformation\Http\Controllers\MissionBriefingController::class, 'acknowledge'])->name('missions.briefing.acknowledge');
    Route::get('dashboard', function (
        ComplianceDashboardSummary $summary,
        CurrentPilotProfile $currentPilot,
        MyPilotWorkspace $pilotWorkspace
    ) {
        $user = request()->user();
        $pilot = $user->hasUasPermission('pilots.self-service')
            ? $currentPilot->resolve($user)
            : null;

        return Inertia::render('dashboard', [
            'summary' => $summary->execute(),
            'pilotWorkspace' => $pilot ? $pilotWorkspace->execute($pilot) : null,
            'pilotOnboardingRequired' => $user->hasUasPermission('pilots.self-service') && $pilot === null,
        ]);
    })->name('dashboard');

    Route::get('my/pilot', [MyPilotProfileController::class, 'show'])->name('my.pilot.show');
    Route::get('my/pilot/create', [MyPilotProfileController::class, 'create'])->name('my.pilot.create');
    Route::post('my/pilot', [MyPilotProfileController::class, 'store'])->name('my.pilot.store');
    Route::get('my/pilot/edit', [MyPilotProfileController::class, 'edit'])->name('my.pilot.edit');
    Route::put('my/pilot', [MyPilotProfileController::class, 'update'])->name('my.pilot.update');
    Route::get('my/compliance', [MyComplianceController::class, 'show'])->name('my.compliance.show');

    Route::put('pilots/{pilot}/user-link', [PilotProfileController::class, 'linkUser'])->name('pilots.user-link.update');
    Route::resource('pilots', PilotProfileController::class)->except(['destroy']);
    Route::resource('training-courses', TrainingCourseController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('regulatory-requirements/{regulatoryRequirement}/supersede', [RegulatoryRequirementController::class, 'supersede'])->name('regulatory-requirements.supersede');
    Route::post('regulatory-requirements/{regulatoryRequirement}/supersede', [RegulatoryRequirementController::class, 'storeSupersedingVersion'])->name('regulatory-requirements.supersede.store');
    Route::resource('regulatory-requirements', RegulatoryRequirementController::class)->only(['index', 'create', 'store', 'show'])->parameters(['regulatory-requirements' => 'regulatoryRequirement']);
    Route::get('regulatory-forms/{regulatoryForm}/supersede', [RegulatoryFormController::class, 'supersede'])->name('regulatory-forms.supersede');
    Route::post('regulatory-forms/{regulatoryForm}/supersede', [RegulatoryFormController::class, 'storeSupersedingVersion'])->name('regulatory-forms.supersede.store');
    Route::resource('regulatory-forms', RegulatoryFormController::class)->only(['index', 'create', 'store', 'show'])->parameters(['regulatory-forms' => 'regulatoryForm']);
    Route::get('regulatory-fees/{regulatoryFee}/supersede', [RegulatoryFeeController::class, 'supersede'])->name('regulatory-fees.supersede');
    Route::post('regulatory-fees/{regulatoryFee}/supersede', [RegulatoryFeeController::class, 'storeSupersedingVersion'])->name('regulatory-fees.supersede.store');
    Route::resource('regulatory-fees', RegulatoryFeeController::class)->only(['index', 'create', 'store', 'show'])->parameters(['regulatory-fees' => 'regulatoryFee']);
    Route::put('regulatory-external-integrations/{regulatoryExternalIntegration}/status', [RegulatoryExternalIntegrationController::class, 'updateStatus'])->name('regulatory-external-integrations.status.update');
    Route::resource('regulatory-external-integrations', RegulatoryExternalIntegrationController::class)->only(['index', 'create', 'store', 'show'])->parameters(['regulatory-external-integrations' => 'regulatoryExternalIntegration']);
    Route::resource('operators', OperatorProfileController::class)->except(['destroy']);
    Route::post('operators/{operator}/memberships', [OperatorMembershipController::class, 'store'])->name('operators.memberships.store');
    Route::put('operator-memberships/{membership}/status', [OperatorMembershipController::class, 'updateStatus'])->name('operator-memberships.status.update');
    Route::post('operators/{operator}/pilots', [OperatorAssignmentController::class, 'storePilot'])->name('operators.pilots.store');
    Route::post('operators/{operator}/aircraft', [OperatorAssignmentController::class, 'storeAircraft'])->name('operators.aircraft.store');
    Route::get('operators/{operator}/certificate-cases/create', [OperatorCertificateCaseController::class, 'create'])->name('operators.certificate-cases.create');
    Route::post('operators/{operator}/certificate-cases', [OperatorCertificateCaseController::class, 'store'])->name('operators.certificate-cases.store');
    Route::get('operator-certificate-cases/{certificateCase}', [OperatorCertificateCaseController::class, 'show'])->name('operator-certificate-cases.show');
    Route::get('operator-certificate-cases/{certificateCase}/application-pack', [ApplicationRenewalPackController::class, 'show'])->name('operator-certificate-cases.application-pack.show');
    Route::get('operator-certificate-cases/{certificateCase}/edit', [OperatorCertificateCaseController::class, 'edit'])->name('operator-certificate-cases.edit');
    Route::put('operator-certificate-cases/{certificateCase}', [OperatorCertificateCaseController::class, 'update'])->name('operator-certificate-cases.update');
    Route::get('operators/{operator}/manual-revisions/create', [OperationsManualRevisionController::class, 'create'])->name('operators.manual-revisions.create');
    Route::post('operators/{operator}/manual-revisions', [OperationsManualRevisionController::class, 'store'])->name('operators.manual-revisions.store');
    Route::get('operations-manual-revisions/{manualRevision}', [OperationsManualRevisionController::class, 'show'])->name('operations-manual-revisions.show');
    Route::get('operations-manual-revisions/{manualRevision}/edit', [OperationsManualRevisionController::class, 'edit'])->name('operations-manual-revisions.edit');
    Route::put('operations-manual-revisions/{manualRevision}', [OperationsManualRevisionController::class, 'update'])->name('operations-manual-revisions.update');
    Route::get('operations-manual-revisions/{manualRevision}/distributions/create', [OperationsManualDistributionController::class, 'create'])->name('operations-manual-revisions.distributions.create');
    Route::post('operations-manual-revisions/{manualRevision}/distributions', [OperationsManualDistributionController::class, 'store'])->name('operations-manual-revisions.distributions.store');
    Route::get('operations-manual-distributions/{distribution}/acknowledge', [OperationsManualAcknowledgementController::class, 'edit'])->name('operations-manual-distributions.acknowledge.edit');
    Route::put('operations-manual-distributions/{distribution}/acknowledge', [OperationsManualAcknowledgementController::class, 'update'])->name('operations-manual-distributions.acknowledge.update');
    Route::get('operations-manual-revisions/{manualRevision}/training-requirements/create', [OperationsManualTrainingRequirementController::class, 'create'])->name('operations-manual-revisions.training-requirements.create');
    Route::post('operations-manual-revisions/{manualRevision}/training-requirements', [OperationsManualTrainingRequirementController::class, 'store'])->name('operations-manual-revisions.training-requirements.store');
    Route::resource('batteries', BatteryController::class)->only(['index', 'create', 'store']);
    Route::resource('aircraft-catalogue', AircraftCatalogueController::class)->only(['index', 'show'])->parameters(['aircraft-catalogue' => 'aircraftModel']);
    Route::resource('aircraft', AircraftController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('evidence-documents', EvidenceDocumentController::class)->only(['index', 'store']);
    Route::resource('defects', AircraftDefectController::class)->only(['index', 'create', 'store']);
    Route::resource('missions', MissionController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('missions/{mission}/release', [MissionController::class, 'release'])->name('missions.release');
    Route::post('missions/{mission}/post-flight-propagation', [MissionController::class, 'propagatePostFlight'])->name('missions.post-flight-propagation');
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
    Route::get('gis-projects/{gisProject}/missions/create', [GisProjectMissionController::class, 'create'])->name('gis-projects.missions.create');
    Route::post('gis-projects/{gisProject}/missions', [GisProjectMissionController::class, 'store'])->name('gis-projects.missions.store');
    Route::get('gis-project-missions/{projectMission}/datasets/create', [GisDatasetController::class, 'create'])->name('gis-project-missions.datasets.create');
    Route::post('gis-project-missions/{projectMission}/datasets', [GisDatasetController::class, 'store'])->name('gis-project-missions.datasets.store');
    Route::get('gis-layers/{spatialLayer}/features/create', [GisFeatureController::class, 'create'])->name('gis-layers.features.create');
    Route::post('gis-layers/{spatialLayer}/features', [GisFeatureController::class, 'store'])->name('gis-layers.features.store');
    Route::put('gis-projects/{gisProject}/transition', [GisProjectController::class, 'transition'])->name('gis-projects.transition');
    Route::resource('gis-projects', GisProjectController::class)->only(['index', 'create', 'store', 'show'])->parameters(['gis-projects' => 'gisProject']);
    Route::get('compliance/register', [ComplianceRegisterController::class, 'index'])->name('compliance.register');
    Route::get('compliance/traceability', [ComplianceTraceabilityController::class, 'index'])->name('compliance.traceability');
    Route::put('compliance-notifications/{complianceNotification}/status', [ComplianceNotificationController::class, 'updateStatus'])->name('compliance-notifications.status.update');
    Route::resource('compliance-notifications', ComplianceNotificationController::class)->only(['index', 'create', 'store', 'show'])->parameters(['compliance-notifications' => 'complianceNotification']);
    Route::get('phase-1/verification', [Phase1VerificationController::class, 'index'])->name('phase-1.verification');
    Route::get('phase-1/verification/export', [Phase1VerificationController::class, 'export'])->name('phase-1.verification.export');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

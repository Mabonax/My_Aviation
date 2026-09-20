<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftReadinessSummary;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Domains\Uas\Aircraft\Domain\Models\UasManufacturer;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Missions\Application\Queries\MissionComplianceSummary;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function missionComplianceUser(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    if ($permissions !== []) {
        $role = UasRole::query()->create([
            'name' => 'mission-compliance-'.uniqid(),
            'label' => 'Mission Compliance Test Role',
            'permissions' => $permissions,
        ]);

        $role->users()->attach($user);
    }

    return $user;
}

function missionComplianceOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create(array_merge([
        'legal_entity' => 'Mission Compliance Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'MCO-'.str()->upper(str()->random(6)),
        'uasoc_number' => 'UASOC-MCO-'.str()->upper(str()->random(4)),
        'certificate_issue_date' => now()->subYear()->toDateString(),
        'certificate_expiry_date' => now()->addYear()->toDateString(),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations Lead',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'operating_bases' => ['Midrand'],
        'approved_aircraft' => [],
        'approved_pilots' => [],
        'operations_specifications' => ['VLOS'],
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission compliance test operator.',
        'responsible_role' => 'Accountable Manager',
    ], $overrides));
}

function missionCompliancePilot(array $overrides = [], array $certificateOverrides = []): UasPilot
{
    $pilot = UasPilot::query()->create(array_merge([
        'first_name' => 'Mission',
        'last_name' => 'Pilot',
        'email' => fake()->unique()->safeEmail(),
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission compliance pilot.',
        'responsible_role' => 'Compliance Manager',
    ], $overrides));

    PilotCertificate::query()->create(array_merge([
        'uas_pilot_id' => $pilot->id,
        'certificate_number' => 'RPC-MCO-'.str()->upper(str()->random(6)),
        'issue_date' => now()->subYear()->toDateString(),
        'expiry_date' => now()->addDays(180)->toDateString(),
        'status' => 'valid',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
    ], $certificateOverrides));

    return $pilot;
}

function missionComplianceAircraft(array $overrides = []): UasAircraft
{
    $manufacturer = UasManufacturer::query()->firstOrCreate(['slug' => 'test-air'], ['name' => 'Test Air', 'status' => 'active']);
    $model = UasAircraftModel::query()->firstOrCreate(
        ['manufacturer_id' => $manufacturer->id, 'model' => 'Release Surveyor'],
        ['aircraft_type' => 'Multirotor', 'status' => 'Current', 'catalogue_status' => 'verified', 'verified_at' => now()->toDateString()],
    );

    $aircraft = UasAircraft::query()->create(array_merge([
        'aircraft_model_id' => $model->id,
        'registration' => 'ZT-MCO-'.str()->upper(str()->random(5)),
        'manufacturer' => $manufacturer->name,
        'model' => $model->model,
        'serial_number' => 'SN-MCO-'.str()->upper(str()->random(8)),
        'operational_status' => 'active_serviceable',
    ], $overrides));

    AircraftRegistration::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'registration_number' => $aircraft->registration,
        'lifecycle_state' => 'active',
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
    ]);

    AircraftApproval::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'approval_type' => 'uasla',
        'approval_number' => 'UASLA-MCO-'.str()->upper(str()->random(6)),
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);

    UasBattery::query()->create([
        'battery_uid' => 'BAT-MCO-'.str()->upper(str()->random(6)),
        'manufacturer' => 'Test Air',
        'model' => 'Pack 1',
        'serial_number' => 'BAT-SN-MCO-'.str()->upper(str()->random(8)),
        'compatible_uas_aircraft_id' => $aircraft->id,
        'cycle_count' => 10,
        'maximum_cycles' => 200,
        'health_status' => 'normal',
        'retirement_status' => 'active',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission readiness battery.',
    ]);

    return $aircraft;
}

function missionComplianceTemplate(): UasChecklistTemplate
{
    return UasChecklistTemplate::query()->updateOrCreate(
        ['type' => 'pre_flight', 'version' => 'MCO-PRE-1'],
        [
            'name' => 'Mission compliance pre-flight',
            'effective_date' => now()->subDay()->toDateString(),
            'active' => true,
            'items' => [['key' => 'aircraft', 'label' => 'Aircraft inspected', 'required' => true, 'sequence' => 1]],
            'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CHK-001',
            'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
            'regulatory_effective_date' => '2026-09-09',
            'regulatory_applicability' => 'Mission release checklist.',
        ],
    );
}

function missionComplianceRecordChecklist(UasMission $mission, string $state = 'completed'): UasMissionChecklist
{
    $template = missionComplianceTemplate();

    return UasMissionChecklist::query()->create([
        'uas_mission_id' => $mission->id,
        'uas_checklist_template_id' => $template->id,
        'performed_by' => null,
        'type' => 'pre_flight',
        'checklist_version' => $template->version,
        'performed_at' => now(),
        'results' => ['aircraft' => ['result' => $state === 'blocked' ? 'fail' : 'pass', 'notes' => null]],
        'exceptions' => $state === 'completed_with_exceptions' ? 'Operations manager review captured.' : null,
        'state' => $state,
    ]);
}

function missionComplianceMission(array $overrides = []): UasMission
{
    $operator = $overrides['operator'] ?? missionComplianceOperator();
    $pilot = $overrides['pilot'] ?? missionCompliancePilot();
    $aircraft = $overrides['aircraft'] ?? missionComplianceAircraft();

    $mission = UasMission::query()->create(array_merge([
        'mission_number' => 'MIS-MCO-'.str()->upper(str()->random(6)),
        'purpose' => 'Mission release readiness test',
        'location' => 'Clear test range',
        'latitude' => -24.0000000,
        'longitude' => 27.0000000,
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator?->id,
        'uas_pilot_id' => $pilot?->id,
        'uas_aircraft_id' => $aircraft?->id,
        'planned_start_at' => now()->addDay(),
        'planned_end_at' => now()->addDay()->addHour(),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 2.5,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => 'Suitable for flight.',
        'airspace_assessment' => 'Clear configured overlay review.',
        'risk_assessment' => ['overall' => 'low'],
        'emergency_arrangements' => 'Emergency landing area briefed.',
        'lifecycle_state' => MissionLifecycleState::Approved,
        'release_gate_state' => 'amber',
        'release_gate_results' => ['state' => 'amber', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001 and FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission compliance release readiness.',
        'responsible_role' => 'Operations Manager',
    ], Arr::except($overrides, ['operator', 'pilot', 'aircraft'])));
    aimPrepareMission($mission);
    return $mission;
}

function missionComplianceMember(User $user, UasOperator $operator, string $role = 'operations_manager'): void
{
    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operator->id,
        'user_id' => $user->id,
        'membership_role' => $role,
        'status' => 'active',
    ]);
}

function missionComplianceAuthorizeUserForMission(User $user, UasMission $mission, string $role = 'operations_manager'): void
{
    $operator = $mission->operator;
    if (! $operator) {
        return;
    }

    missionComplianceMember($user, $operator, $role);

    if ($mission->uas_aircraft_id) {
        $operator->aircraft()->syncWithoutDetaching([
            $mission->uas_aircraft_id => ['assignment_role' => 'operated_aircraft', 'status' => 'active'],
        ]);
    }
}

function missionComplianceRefreshStoredGate(UasMission $mission): void
{
    $summary = app(MissionComplianceSummary::class)->execute($mission);

    $mission->forceFill([
        'release_gate_state' => $summary['status'],
        'release_gate_results' => $summary,
    ])->save();
}

it('returns green mission readiness when all release controls are satisfied', function () {
    $mission = missionComplianceMission();
    missionComplianceRecordChecklist($mission);

    $summary = app(MissionComplianceSummary::class)->execute($mission);

    expect($summary['status'])->toBe('green')
        ->and($summary['blocking_count'])->toBe(0)
        ->and($summary['warning_count'])->toBe(0)
        ->and($summary['controls'])->toHaveCount(8);
});

it('propagates red and amber aircraft readiness into mission compliance', function () {
    $redAircraft = missionComplianceAircraft(['operational_status' => 'grounded']);
    $redMission = missionComplianceMission(['aircraft' => $redAircraft]);
    missionComplianceRecordChecklist($redMission);

    $amberAircraft = missionComplianceAircraft(['aircraft_model_id' => null]);
    $amberMission = missionComplianceMission(['aircraft' => $amberAircraft]);
    missionComplianceRecordChecklist($amberMission);

    $aircraftSummary = app(AircraftReadinessSummary::class)->execute($redAircraft);
    $redSummary = app(MissionComplianceSummary::class)->execute($redMission);
    $amberSummary = app(MissionComplianceSummary::class)->execute($amberMission);

    expect($aircraftSummary['status'])->toBe('red')
        ->and(collect($redSummary['controls'])->firstWhere('key', 'aircraft_readiness')['details']['aircraft_readiness']['status'])->toBe('red')
        ->and($redSummary['status'])->toBe('red')
        ->and($amberSummary['status'])->toBe('amber')
        ->and(collect($amberSummary['controls'])->firstWhere('key', 'aircraft_readiness')['status'])->toBe('amber');
});

it('marks expired pilot credentials and inactive operators as red', function () {
    $expiredPilot = missionCompliancePilot([], ['expiry_date' => now()->subDay()->toDateString()]);
    $inactiveOperator = missionComplianceOperator(['status' => 'suspended']);
    $mission = missionComplianceMission(['pilot' => $expiredPilot, 'operator' => $inactiveOperator]);
    missionComplianceRecordChecklist($mission);

    $summary = app(MissionComplianceSummary::class)->execute($mission);

    expect($summary['status'])->toBe('red')
        ->and(collect($summary['controls'])->firstWhere('key', 'pilot_readiness')['status'])->toBe('red')
        ->and(collect($summary['controls'])->firstWhere('key', 'operator_compliance')['status'])->toBe('red');
});

it('maps geometry warnings and blocking airspace to amber and red controls', function () {
    $warningMission = missionComplianceMission(['latitude' => -25.9700000, 'longitude' => 28.1000000, 'location' => 'Strategic review sample']);
    missionComplianceRecordChecklist($warningMission);
    $blockedMission = missionComplianceMission(['latitude' => -26.0000000, 'longitude' => 28.1200000, 'location' => 'Controlled airspace sample']);
    missionComplianceRecordChecklist($blockedMission);

    $warningSummary = app(MissionComplianceSummary::class)->execute($warningMission);
    $blockedSummary = app(MissionComplianceSummary::class)->execute($blockedMission);

    expect($warningSummary['status'])->toBe('amber')
        ->and(collect($warningSummary['controls'])->firstWhere('key', 'geometry_airspace')['status'])->toBe('amber')
        ->and($blockedSummary['status'])->toBe('red')
        ->and(collect($blockedSummary['controls'])->firstWhere('key', 'geometry_airspace')['status'])->toBe('red');
});

it('blocks release for incomplete checklist and missing mandatory approval evidence', function () {
    missionComplianceTemplate();
    $checklistMission = missionComplianceMission();
    $approvalMission = missionComplianceMission(['latitude' => -26.0000000, 'longitude' => 28.1200000, 'location' => 'Controlled airspace sample']);
    missionComplianceRecordChecklist($approvalMission);

    $checklistSummary = app(MissionComplianceSummary::class)->execute($checklistMission);
    $approvalSummary = app(MissionComplianceSummary::class)->execute($approvalMission);

    expect(collect($checklistSummary['controls'])->firstWhere('key', 'pre_flight_checklist')['status'])->toBe('red')
        ->and(collect($approvalSummary['controls'])->firstWhere('key', 'mission_approvals')['status'])->toBe('red');
});

it('allows green and amber mission release but rejects red release server side', function () {
    $user = missionComplianceUser(['missions.view', 'missions.update']);
    $greenMission = missionComplianceMission();
    missionComplianceAuthorizeUserForMission($user, $greenMission);
    missionComplianceRecordChecklist($greenMission);
    $amberMission = missionComplianceMission(['risk_assessment' => null]);
    missionComplianceAuthorizeUserForMission($user, $amberMission);
    missionComplianceRecordChecklist($amberMission);
    $redMission = missionComplianceMission();
    missionComplianceAuthorizeUserForMission($user, $redMission);
    missionComplianceTemplate();

    $this->actingAs($user)->post(route('missions.release', $greenMission))->assertRedirect(route('missions.show', $greenMission));
    $this->actingAs($user)->post(route('missions.release', $amberMission))->assertRedirect(route('missions.show', $amberMission));
    $this->actingAs($user)->post(route('missions.release', $redMission))->assertInvalid(['mission']);

    expect($greenMission->refresh()->lifecycle_state)->toBe(MissionLifecycleState::ReadyForFlight)
        ->and($amberMission->refresh()->release_gate_state)->toBe('amber')
        ->and($redMission->refresh()->lifecycle_state)->toBe(MissionLifecycleState::Approved);
});

it('records immutable release audit evidence with the compliance snapshot', function () {
    $user = missionComplianceUser(['missions.view', 'missions.update']);
    $mission = missionComplianceMission();
    missionComplianceAuthorizeUserForMission($user, $mission);
    missionComplianceRecordChecklist($mission);

    $this->actingAs($user)->post(route('missions.release', $mission))->assertRedirect();

    $audit = UasAuditEntry::query()->where('action', 'mission.released')->firstOrFail();

    expect($audit->new_values['release_evidence']['mission_id'])->toBe($mission->id)
        ->and($audit->new_values['release_evidence']['compliance_status'])->toBe('green')
        ->and($audit->new_values['release_evidence']['blocking_count'])->toBe(0)
        ->and($audit->new_values['release_evidence']['aircraft_readiness_status'])->toBe('green')
        ->and($audit->new_values['release_evidence']['control_snapshot'])->toHaveCount(8)
        ->and($audit->new_values['release_evidence']['aeronautical_briefing_id'])->toBe(\App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing::where('mission_id', $mission->id)->value('id'))
        ->and($audit->new_values['release_evidence']['aeronautical_briefing_revision'])->toBe(1);
});

it('returns structured mission compliance through tenant-scoped API endpoints', function () {
    $member = missionComplianceUser();
    $operatorA = missionComplianceOperator(['legal_entity' => 'Mission API Operator A']);
    $operatorB = missionComplianceOperator(['legal_entity' => 'Mission API Operator B']);
    missionComplianceMember($member, $operatorA);
    $missionA = missionComplianceMission(['operator' => $operatorA]);
    missionComplianceRecordChecklist($missionA);
    missionComplianceRefreshStoredGate($missionA);
    $missionB = missionComplianceMission(['operator' => $operatorB]);
    missionComplianceRecordChecklist($missionB);
    missionComplianceRefreshStoredGate($missionB);

    Sanctum::actingAs($member);

    $this->getJson('/api/v1/missions')
        ->assertOk()
        ->assertJsonPath('data.missions.0.compliance.status', 'green')
        ->assertJsonMissing(['mission_number' => $missionB->mission_number]);

    $this->getJson("/api/v1/missions/{$missionA->id}/compliance")
        ->assertOk()
        ->assertJsonPath('data.compliance.status', 'green')
        ->assertJsonPath('data.compliance.controls.0.key', 'aircraft_readiness')
        ->assertJsonPath('meta.contract_version', 'v1.0');

    $this->getJson("/api/v1/missions/{$missionB->id}/compliance")
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('renders mission detail release readiness for authenticated users', function () {
    $this->withoutVite();

    $user = missionComplianceUser(['missions.view']);
    $mission = missionComplianceMission();
    missionComplianceAuthorizeUserForMission($user, $mission, 'remote_pilot');
    missionComplianceRecordChecklist($mission);

    $this->actingAs($user)
        ->get(route('missions.show', $mission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('missions/show')
            ->where('missionCompliance.status', 'green')
            ->where('mission.compliance.status', 'green')
        );
});

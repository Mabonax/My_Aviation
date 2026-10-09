<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Models\UasMissionBatteryUsage;
use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\FlightFolios\Domain\Models\AircraftFlightFolio;
use App\Domains\Uas\FlightLogs\Domain\Models\PilotLogEntry;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Tracks\Domain\Models\UasFlightTrack;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function postFlightClosurePayload(array $overrides = []): array
{
    return array_merge([
        'actual_takeoff_at' => now()->subMinutes(70)->format('Y-m-d\TH:i'),
        'actual_landing_at' => now()->subMinutes(10)->format('Y-m-d\TH:i'),
        'pilot_confirmed' => true,
        'aircraft_confirmed' => true,
        'defects_declared' => true,
        'occurrence_declared' => false,
        'closure_notes' => 'PIC confirmed mission close-out.',
    ], $overrides);
}

function postFlightPropagationUser(array $permissions = ['missions.view', 'missions.update']): User
{
    $user = User::factory()->create();
    $role = UasRole::query()->create([
        'name' => 'post-flight-propagation-'.uniqid(),
        'label' => 'Post Flight Propagation Test Role',
        'permissions' => $permissions,
    ]);
    $role->users()->attach($user);

    return $user;
}

function postFlightPropagationOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Post Flight Operator '.str()->upper(str()->random(5)),
        'trading_name' => 'Post Flight Operator',
        'status' => 'active',
        'accountable_manager' => 'Post Flight Accountable Manager',
        'responsible_person_flight_operations' => 'Post Flight Operations',
        'responsible_person_aircraft' => 'Post Flight Aircraft Lead',
        'regulatory_source' => 'TR-010 post-flight tenancy fixture',
        'regulatory_source_version' => 'v1',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Post-flight propagation tenant verification.',
        'responsible_role' => 'Operations Manager',
    ]);
}

function postFlightAuthorize(User $user, UasMission $mission, string $role = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): void
{
    $operator = $mission->operator;
    if (! $operator) {
        return;
    }

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operator->id,
        'user_id' => $user->id,
        'membership_role' => $role,
        'status' => UasOperatorMembership::STATUS_ACTIVE,
    ]);

    if ($mission->uas_aircraft_id) {
        $operator->aircraft()->syncWithoutDetaching([
            $mission->uas_aircraft_id => ['assignment_role' => 'operated_aircraft', 'status' => 'active'],
        ]);
    }
}

function postFlightPropagationPilot(): UasPilot
{
    return UasPilot::query()->create([
        'first_name' => 'Closeout',
        'last_name' => 'Pilot',
        'email' => fake()->unique()->safeEmail(),
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Post-flight propagation pilot.',
        'responsible_role' => 'Compliance Manager',
    ]);
}

function postFlightPropagationAircraft(): UasAircraft
{
    return UasAircraft::query()->create([
        'registration' => 'ZT-PF-'.str()->upper(str()->random(5)),
        'manufacturer' => 'YAW',
        'model' => 'Propagation Surveyor',
        'serial_number' => 'SN-PF-'.str()->upper(str()->random(8)),
        'operational_status' => 'active_serviceable',
    ]);
}

function postFlightPropagationMission(array $overrides = []): UasMission
{
    $operator = $overrides['operator'] ?? postFlightPropagationOperator();
    $pilot = $overrides['pilot'] ?? postFlightPropagationPilot();
    $aircraft = $overrides['aircraft'] ?? postFlightPropagationAircraft();

    return UasMission::query()->create(array_merge([
        'uas_operator_id' => $operator->id,
        'mission_number' => 'MIS-PF-'.str()->upper(str()->random(6)),
        'purpose' => 'Post-flight propagation test',
        'location' => 'Midrand test range',
        'takeoff_point' => ['latitude' => -25.999, 'longitude' => 28.123, 'label' => 'Pad A'],
        'landing_point' => ['latitude' => -25.998, 'longitude' => 28.124, 'label' => 'Pad B'],
        'operation_category' => 'inspection',
        'uas_pilot_id' => $pilot?->id,
        'uas_aircraft_id' => $aircraft?->id,
        'planned_start_at' => now()->subHours(2),
        'planned_end_at' => now()->subHour(),
        'maximum_altitude_ft' => 300,
        'planned_distance_km' => 3.2,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => 'Suitable for close-out.',
        'airspace_assessment' => 'Reviewed.',
        'risk_assessment' => ['overall' => 'low'],
        'emergency_arrangements' => 'Briefed.',
        'lifecycle_state' => MissionLifecycleState::Completed,
        'release_gate_state' => 'green',
        'release_gate_results' => ['checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission post-flight propagation.',
        'responsible_role' => 'Operations Manager',
    ], Arr::except($overrides, ['operator', 'pilot', 'aircraft'])));
}

function postFlightPropagationChecklist(UasMission $mission, string $state = 'completed', ?string $exceptions = null): UasMissionChecklist
{
    $template = UasChecklistTemplate::query()->updateOrCreate(
        ['type' => 'post_flight', 'version' => 'PF-PROP-1'],
        [
            'name' => 'Propagation post-flight',
            'effective_date' => now()->subDay()->toDateString(),
            'active' => true,
            'items' => [
                ['key' => 'flight_log_completed', 'label' => 'Flight log completed', 'required' => true, 'sequence' => 1],
                ['key' => 'incidents_or_defects_recorded', 'label' => 'Incidents or defects recorded', 'required' => true, 'sequence' => 2],
            ],
            'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CHK-002',
            'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
            'regulatory_effective_date' => '2026-09-09',
            'regulatory_applicability' => 'Post-flight propagation checklist.',
        ],
    );

    return UasMissionChecklist::query()->create([
        'uas_mission_id' => $mission->id,
        'uas_checklist_template_id' => $template->id,
        'performed_by' => null,
        'type' => 'post_flight',
        'checklist_version' => $template->version,
        'performed_at' => now(),
        'results' => [
            'flight_log_completed' => ['result' => $state === 'blocked' ? 'fail' : 'pass', 'notes' => null],
            'incidents_or_defects_recorded' => ['result' => 'pass', 'notes' => null],
        ],
        'exceptions' => $exceptions,
        'state' => $state,
    ]);
}

function postFlightPropagationEvidence(UasMission $mission, User $actor): void
{
    $battery = UasBattery::query()->create([
        'battery_uid' => 'BAT-PF-'.str()->upper(str()->random(6)),
        'manufacturer' => 'YAW',
        'model' => 'Pack',
        'serial_number' => 'BAT-SN-PF-'.str()->upper(str()->random(8)),
        'compatible_uas_aircraft_id' => $mission->uas_aircraft_id,
        'cycle_count' => 25,
        'maximum_cycles' => 200,
        'health_status' => 'normal',
        'retirement_status' => 'active',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission battery usage.',
    ]);

    UasMissionBatteryUsage::query()->create([
        'uas_mission_id' => $mission->id,
        'uas_battery_id' => $battery->id,
        'recorded_by' => $actor->id,
        'cycles_added' => 2,
        'state_of_charge_start' => 96,
        'state_of_charge_end' => 41,
        'used_at' => now()->subHour(),
        'notes' => 'Normal discharge.',
    ]);

    UasFlightTrack::query()->create([
        'uas_mission_id' => $mission->id,
        'captured_by' => $actor->id,
        'source_type' => 'manual_upload',
        'track_reference' => 'TRK-PF-001',
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'points' => [['latitude' => -25.999, 'longitude' => 28.123]],
        'point_count' => 1,
        'total_distance_km' => 1.234,
        'max_altitude_ft' => 250,
        'anomalies' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Flight track evidence.',
    ]);

    UasAircraftDefect::query()->create([
        'uas_aircraft_id' => $mission->uas_aircraft_id,
        'uas_mission_id' => $mission->id,
        'reported_by' => $actor->id,
        'defect_number' => 'DEF-PF-'.str()->upper(str()->random(6)),
        'source' => 'post_flight',
        'severity' => 'minor',
        'status' => 'open',
        'serviceability_impact' => 'maintenance_required',
        'title' => 'Propeller nick',
        'description' => 'Small propeller nick found during post-flight inspection.',
        'immediate_action' => 'Replace before next sortie.',
        'reported_at' => now(),
        'evidence_references' => ['photo-pf-001'],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-DEF-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Post-flight defect follow-up.',
    ]);
}

it('propagates completed mission close-out into pilot logbook aircraft folio and audit evidence', function () {
    $user = postFlightPropagationUser();
    $mission = postFlightPropagationMission();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission, 'completed_with_exceptions', 'Propeller nick deferred to maintenance follow-up.');
    postFlightPropagationEvidence($mission, $user);

    $this->actingAs($user)
        ->post(route('missions.post-flight-propagation', $mission), postFlightClosurePayload())
        ->assertRedirect(route('missions.show', $mission));

    $pilotLog = PilotLogEntry::query()->where('uas_mission_id', $mission->id)->firstOrFail();
    $folio = AircraftFlightFolio::query()->where('uas_mission_id', $mission->id)->firstOrFail();
    $audit = UasAuditEntry::query()->where('action', 'mission.post_flight_propagated')->firstOrFail();

    expect($mission->refresh()->lifecycle_state)->toBe(MissionLifecycleState::PostFlightReview)
        ->and($mission->post_flight_propagation_state)->toBe('propagated_with_follow_up')
        ->and($mission->actual_flight_duration_minutes)->toBe(60)
        ->and($mission->post_flight_declaration['pilot_confirmed'])->toBeTrue()
        ->and($pilotLog->aircraft_registration)->toBe($mission->aircraft->registration)
        ->and((float) $pilotLog->flight_hours)->toBe(1.0)
        ->and($pilotLog->launch_location)->toBe('Pad A')
        ->and($pilotLog->evidence_references['post_flight_checklist_state'])->toBe('completed_with_exceptions')
        ->and($folio->folio_reference)->toBe('PF-'.$mission->mission_number)
        ->and($folio->battery_cycles)->toBe(2)
        ->and($folio->available_offline)->toBeTrue()
        ->and($folio->maintenance_certification_entries[0]['follow_up_required'])->toBeTrue()
        ->and($audit->new_values['actual_flight_duration_minutes'])->toBe(60)
        ->and($audit->new_values['pilot_log_entry_id'])->toBe($pilotLog->id)
        ->and($audit->new_values['aircraft_flight_folio_id'])->toBe($folio->id);
});

it('is idempotent when post-flight propagation is run more than once', function () {
    $user = postFlightPropagationUser();
    $mission = postFlightPropagationMission();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission);
    postFlightPropagationEvidence($mission, $user);

    $this->actingAs($user)->post(route('missions.post-flight-propagation', $mission), postFlightClosurePayload())->assertRedirect();
    $this->actingAs($user)->post(route('missions.post-flight-propagation', $mission), postFlightClosurePayload(['closure_notes' => 'Second attempt.']))->assertInvalid(['mission']);

    expect(PilotLogEntry::query()->where('uas_mission_id', $mission->id)->count())->toBe(1)
        ->and(AircraftFlightFolio::query()->where('uas_mission_id', $mission->id)->count())->toBe(1)
        ->and(UasAuditEntry::query()->where('auditable_type', UasMission::class)->where('auditable_id', $mission->id)->where('action', 'mission.post_flight_propagated')->count())->toBe(1);
});

it('blocks propagation until mission is completed and post-flight checklist is clear', function () {
    $user = postFlightPropagationUser();
    $plannedMission = postFlightPropagationMission(['lifecycle_state' => MissionLifecycleState::Planning]);
    postFlightAuthorize($user, $plannedMission);
    postFlightPropagationChecklist($plannedMission);
    $blockedMission = postFlightPropagationMission();
    postFlightAuthorize($user, $blockedMission);
    postFlightPropagationChecklist($blockedMission, 'blocked');

    $this->actingAs($user)->post(route('missions.post-flight-propagation', $plannedMission), postFlightClosurePayload())->assertInvalid(['mission']);
    $this->actingAs($user)->post(route('missions.post-flight-propagation', $blockedMission), postFlightClosurePayload())->assertInvalid(['post_flight_checklist']);

    expect(PilotLogEntry::query()->whereIn('uas_mission_id', [$plannedMission->id, $blockedMission->id])->count())->toBe(0)
        ->and(AircraftFlightFolio::query()->whereIn('uas_mission_id', [$plannedMission->id, $blockedMission->id])->count())->toBe(0);
});

it('exposes and writes post-flight propagation through API V1', function () {
    $user = postFlightPropagationUser();
    $mission = postFlightPropagationMission();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission);
    postFlightPropagationEvidence($mission, $user);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/missions/{$mission->id}/post-flight-propagation")
        ->assertOk()
        ->assertJsonPath('data.post_flight_propagation.can_propagate', true)
        ->assertJsonPath('meta.contract_version', 'v1.0');

    $this->postJson("/api/v1/missions/{$mission->id}/post-flight-propagation", postFlightClosurePayload([
        'actual_flight_duration_minutes' => 999,
        'post_flight_propagation_state' => 'propagated',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['actual_flight_duration_minutes', 'post_flight_propagation_state']);

    $this->postJson("/api/v1/missions/{$mission->id}/post-flight-propagation", postFlightClosurePayload())
        ->assertOk()
        ->assertJsonPath('data.post_flight_propagation.state', 'propagated_with_follow_up')
        ->assertJsonPath('data.post_flight_propagation.battery_cycles_summarised', 2)
        ->assertJsonPath('data.post_flight_propagation.actual_flight_duration_minutes', 60)
        ->assertJsonPath('meta.contract_version', 'v1.0');
});

it('renders the mission close-out propagation panel', function () {
    $this->withoutVite();

    $user = postFlightPropagationUser(['missions.view']);
    $mission = postFlightPropagationMission();
    postFlightAuthorize($user, $mission, UasOperatorMembership::ROLE_REMOTE_PILOT);
    postFlightPropagationChecklist($mission);

    $this->actingAs($user)
        ->get(route('missions.show', $mission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('missions/show')
            ->where('postFlightPropagation.can_propagate', true)
            ->where('postFlightPropagation.actual_takeoff_at', null)
            ->where('mission.post_flight_propagation.state', 'pending')
        );
});



function telemetryCsvForMission(UasMission $mission): string
{
    $serial = $mission->aircraft->serial_number;
    return "recorded_at,latitude,longitude,altitude_ft,aircraft_serial,event\n"
        ."2026-01-01T08:00:00Z,-25.9,28.1,0,{$serial},takeoff\n"
        ."2026-01-01T08:30:00Z,-25.8,28.2,250,{$serial},sample\n"
        ."2026-01-01T09:00:00Z,-25.9,28.1,0,{$serial},landing\n";
}

function telemetryDeclarations(): array
{
    return ['telemetry_confirmed' => true, 'pilot_confirmed' => true,
        'aircraft_confirmed' => true, 'defects_declared' => false,
        'occurrence_declared' => false, 'closure_notes' => 'Reviewed flight samples.'];
}

it('reviews and accepts telemetry through the authenticated web workspace', function () {
    $mission = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission);
    $this->actingAs($user)->withSession(['yaw_operator_id' => $mission->uas_operator_id]);
    $url = "/missions/{$mission->id}/telemetry";
    $this->get($url)->assertInertia(fn ($page) => $page->component('missions/telemetry')
        ->where('mission.can_import', true)->has('imports.data', 0));
    $this->post($url, ['file' => \Illuminate\Http\UploadedFile::fake()
        ->createWithContent('flight.csv', telemetryCsvForMission($mission))])->assertRedirect($url);
    $import = \App\Domains\Uas\Telemetry\Domain\Models\TelemetryImport::query()->firstOrFail();
    expect(PilotLogEntry::query()->count())->toBe(0);
    $this->get($url)->assertInertia(fn ($page) => $page
        ->has('imports.data', 1)->missing('imports.data.0.raw_csv')
        ->missing('imports.data.0.flight.points'));
    $this->from($url)->post($url."/{$import->id}/accept", [])->assertSessionHasErrors('telemetry_confirmed');
    expect($import->refresh()->state)->toBe('pending_review');
    $this->post($url."/{$import->id}/accept", telemetryDeclarations())->assertRedirect($url);
    $this->get($url)->assertInertia(fn ($page) => $page
        ->where('mission.can_import', false)->where('imports.data.0.state', 'accepted'));
    expect(PilotLogEntry::query()->count())->toBe(1)->and(AircraftFlightFolio::query()->count())->toBe(1);
});

it('protects web telemetry evidence from other operators and read-only users', function () {
    $mission = postFlightPropagationMission();
    $foreign = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission, UasOperatorMembership::ROLE_REMOTE_PILOT);
    $this->actingAs($user)->withSession(['yaw_operator_id' => $mission->uas_operator_id]);
    $this->get("/missions/{$foreign->id}/telemetry")->assertNotFound();
    $this->post("/missions/{$mission->id}/telemetry")->assertForbidden();
});

it('never turns planned times into actual evidence when called directly', function () {
    $mission = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightPropagationChecklist($mission);
    expect(fn () => app(\App\Domains\Uas\Missions\Application\Actions\PropagatePostFlightRecords::class)
        ->execute($mission, $user))->toThrow(\Illuminate\Validation\ValidationException::class);
    expect(PilotLogEntry::query()->count())->toBe(0)
        ->and(AircraftFlightFolio::query()->count())->toBe(0);
});

it('stages telemetry without changing records then propagates once after review through API V1', function () {
    $mission = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission);
    Sanctum::actingAs($user);
    $url = "/api/v1/missions/{$mission->id}/telemetry-imports";
    $upload = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('flight.csv', telemetryCsvForMission($mission));
    $response = $this->post($url, ['file' => $upload()], ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('data.telemetry_import.state', 'pending_review')
        ->assertJsonPath('data.telemetry_import.flight.point_count', 3);
    $id = $response->json('data.telemetry_import.id');
    $this->post($url, ['file' => $upload()], ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('data.telemetry_import.id', $id);
    expect($mission->refresh()->actual_takeoff_at)->toBeNull()
        ->and(PilotLogEntry::query()->count())->toBe(0)
        ->and(UasFlightTrack::query()->count())->toBe(0);
    $this->getJson($url)->assertOk()->assertJsonMissingPath('data.telemetry_imports.data.0.raw_csv');
    $accept = $url."/{$id}/accept";
    $this->postJson($accept, telemetryDeclarations())->assertOk()
        ->assertJsonPath('data.telemetry_import.state', 'accepted');
    $this->postJson($accept, telemetryDeclarations())->assertOk();
    expect(PilotLogEntry::query()->count())->toBe(1)
        ->and(AircraftFlightFolio::query()->count())->toBe(1)
        ->and(UasFlightTrack::query()->count())->toBe(1)
        ->and((float) PilotLogEntry::query()->firstOrFail()->flight_hours)->toBe(1.0)
        ->and(UasAuditEntry::query()->where('action', 'telemetry.accepted')->count())->toBe(1);
});

it('rolls back track and propagation when the post-flight checklist is blocked', function () {
    $mission = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission, 'blocked');
    $action = app(\App\Domains\Uas\Telemetry\Application\Actions\ImportMissionTelemetry::class);
    $import = $action->stage($mission, $user, telemetryCsvForMission($mission));
    expect(fn () => $action->accept($mission, $import, $user, telemetryDeclarations()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect($import->refresh()->state)->toBe('pending_review')
        ->and(UasFlightTrack::query()->count())->toBe(0)
        ->and(PilotLogEntry::query()->count())->toBe(0)
        ->and($mission->refresh()->post_flight_propagated_at)->toBeNull();
});

it('rejects a flight reused across missions of the same operator', function () {
    $mission = postFlightPropagationMission();
    $other = postFlightPropagationMission(['operator' => $mission->operator, 'aircraft' => $mission->aircraft]);
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    $action = app(\App\Domains\Uas\Telemetry\Application\Actions\ImportMissionTelemetry::class);
    $action->stage($mission, $user, telemetryCsvForMission($mission));
    expect(fn () => $action->stage($other, $user, telemetryCsvForMission($other)))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('protects existing actual times and records already propagated through manual close-out', function () {
    $mission = postFlightPropagationMission(['actual_takeoff_at' => '2026-01-01 07:00:00']);
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    postFlightPropagationChecklist($mission);
    $action = app(\App\Domains\Uas\Telemetry\Application\Actions\ImportMissionTelemetry::class);
    $import = $action->stage($mission, $user, telemetryCsvForMission($mission));
    expect(fn () => $action->accept($mission, $import, $user, telemetryDeclarations()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    $mission->forceFill(['post_flight_propagated_at' => now()])->save();
    expect(fn () => $action->accept($mission, $import, $user, telemetryDeclarations()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect(UasFlightTrack::query()->count())->toBe(0);
});

it('requires confirmation and rejects a changed aircraft or pilot after staging', function () {
    $mission = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    $action = app(\App\Domains\Uas\Telemetry\Application\Actions\ImportMissionTelemetry::class);
    $import = $action->stage($mission, $user, telemetryCsvForMission($mission));
    expect(fn () => $action->accept($mission, $import, $user, []))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    $mission->update(['uas_pilot_id' => postFlightPropagationPilot()->id]);
    expect(fn () => $action->accept($mission, $import, $user, telemetryDeclarations()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('rejects wrong aircraft identity and missions that are still in planning', function () {
    $mission = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    $action = app(\App\Domains\Uas\Telemetry\Application\Actions\ImportMissionTelemetry::class);
    $csv = str_replace($mission->aircraft->serial_number, 'WRONG-SERIAL', telemetryCsvForMission($mission));
    expect(fn () => $action->stage($mission, $user, $csv))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    $mission->update(['lifecycle_state' => MissionLifecycleState::Planning]);
    expect(fn () => $action->stage($mission, $user, telemetryCsvForMission($mission)))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('enforces operator isolation and read-only membership on telemetry endpoints', function () {
    $mission = postFlightPropagationMission();
    $foreign = postFlightPropagationMission();
    $user = postFlightPropagationUser();
    postFlightAuthorize($user, $mission);
    Sanctum::actingAs($user);
    $this->getJson("/api/v1/missions/{$foreign->id}/telemetry-imports")->assertNotFound();
    $reader = postFlightPropagationUser(['missions.view']);
    postFlightAuthorize($reader, $mission, UasOperatorMembership::ROLE_REMOTE_PILOT);
    Sanctum::actingAs($reader);
    $this->postJson("/api/v1/missions/{$mission->id}/telemetry-imports")->assertForbidden();
});

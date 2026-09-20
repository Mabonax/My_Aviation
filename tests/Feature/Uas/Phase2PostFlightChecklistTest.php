<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function postFlightOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Post-flight Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'POST-'.str()->upper(str()->random(5)),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'regulatory_source' => 'YAW TR-010 post-flight checklist tenancy verification',
        'regulatory_source_version' => 'TR-010',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Phase 2 tenant-aware test fixture.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function postFlightMission(array $overrides = [], ?UasOperator $operator = null): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-POST-001',
        'purpose' => 'Post-flight checklist proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Post-flight test range',
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator?->id,
        'uas_aircraft_id' => null,
        'uas_pilot_id' => null,
        'planned_start_at' => now()->subHours(3),
        'planned_end_at' => now()->subHour(),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 1.2,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => null,
        'airspace_assessment' => null,
        'approvals' => [],
        'risk_assessment' => [],
        'emergency_arrangements' => null,
        'lifecycle_state' => 'post_flight_review',
        'release_gate_state' => 'green',
        'release_gate_results' => ['state' => 'green', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CHK-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Post-flight checklist verification mission.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function postFlightUser(array $permissions = ['missions.view', 'missions.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'post_flight_checker',
        'label' => 'Post-flight Checker',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    if ($operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => $membershipRole,
            'status' => UasOperatorMembership::STATUS_ACTIVE,
            'source' => UasOperatorMembership::SOURCE_ADMIN,
            'activated_at' => now(),
        ]);
    }

    return $user;
}

function passingPostFlightResults(?UasChecklistTemplate $template = null, array $overrides = []): array
{
    $template ??= UasChecklistTemplate::query()->where('type', 'post_flight')->firstOrFail();

    return collect($template->items)->mapWithKeys(fn (array $item): array => [
        $item['key'] => ['result' => $overrides[$item['key']]['result'] ?? 'pass', 'notes' => $overrides[$item['key']]['notes'] ?? null],
    ])->all();
}

it('seeds a versioned post-flight checklist template for FR-CHK-002', function () {
    $template = UasChecklistTemplate::query()->where('type', 'post_flight')->firstOrFail();

    expect($template->version)->toBe('FR-CHK-002-v1')
        ->and($template->active)->toBeTrue()
        ->and($template->items)->toHaveCount(10)
        ->and(collect($template->items)->pluck('label'))->toContain('Flight log completed', 'Incidents or defects recorded')
        ->and($template->regulatory_source)->toContain('FR-CHK-002');
});

it('requires mission update permission to open and record a post-flight checklist', function () {
    $this->withoutVite();

    $operator = postFlightOperator();
    $mission = postFlightMission([], $operator);
    $viewer = postFlightUser(['missions.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get("/missions/{$mission->id}/post-flight-checklist")->assertForbidden();
    $this->actingAs($viewer)->post("/missions/{$mission->id}/post-flight-checklist", ['results' => passingPostFlightResults()])->assertForbidden();
});

it('records post-flight performer timestamp version results exceptions and audit evidence', function () {
    $operator = postFlightOperator();
    $user = postFlightUser([], $operator);
    $mission = postFlightMission([], $operator);
    $template = UasChecklistTemplate::query()->where('type', 'post_flight')->firstOrFail();
    $results = passingPostFlightResults($template, [
        'flight_log_completed' => ['result' => 'pass', 'notes' => 'Logbook entry captured.'],
    ]);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/post-flight-checklist", [
            'results' => $results,
            'exceptions' => 'Minor payload scuff noted for maintenance inspection.',
        ])
        ->assertRedirect(route('missions.show', $mission));

    $checklist = UasMissionChecklist::query()->firstOrFail();

    expect($checklist->type)->toBe('post_flight')
        ->and($checklist->performed_by)->toBe($user->id)
        ->and($checklist->checklist_version)->toBe('FR-CHK-002-v1')
        ->and($checklist->performed_at)->not->toBeNull()
        ->and($checklist->results['flight_log_completed']['notes'])->toBe('Logbook entry captured.')
        ->and($checklist->exceptions)->toBe('Minor payload scuff noted for maintenance inspection.')
        ->and($checklist->state)->toBe('completed_with_exceptions');

    $audit = UasAuditEntry::query()->where('action', 'mission_checklist.post_flight.recorded')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasMissionChecklist::class)
        ->and($audit->auditable_id)->toBe($checklist->id)
        ->and($audit->requirement_id)->toBe('FR-CHK-002');
});

it('blocks the post-flight checklist state when a required close-out item fails', function () {
    $operator = postFlightOperator();
    $user = postFlightUser([], $operator);
    $mission = postFlightMission([], $operator);
    $template = UasChecklistTemplate::query()->where('type', 'post_flight')->firstOrFail();
    $results = passingPostFlightResults($template, [
        'incidents_or_defects' => ['result' => 'fail', 'notes' => 'Defect not yet raised.'],
    ]);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/post-flight-checklist", ['results' => $results])
        ->assertRedirect(route('missions.show', $mission));

    $checklist = UasMissionChecklist::query()->firstOrFail();

    expect($checklist->state)->toBe('blocked')
        ->and($checklist->results['incidents_or_defects']['result'])->toBe('fail');
});

it('exposes the active post-flight template and latest checklist on mission screens', function () {
    $this->withoutVite();

    $operator = postFlightOperator();
    $user = postFlightUser([], $operator);
    $mission = postFlightMission([], $operator);

    $this->actingAs($user)
        ->get("/missions/{$mission->id}/post-flight-checklist")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/checklists/post-flight')
            ->where('checklist.template.version', 'FR-CHK-002-v1')
            ->has('checklist.template.items', 10)
        );

    $this->actingAs($user)->post("/missions/{$mission->id}/post-flight-checklist", [
        'results' => passingPostFlightResults(),
    ]);

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->where('postFlightChecklist.latest.checklist_version', 'FR-CHK-002-v1')
            ->where('postFlightChecklist.latest.state', 'completed')
        );
});
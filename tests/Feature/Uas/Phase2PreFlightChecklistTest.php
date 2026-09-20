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

function checklistOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'checklist Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'CHE-'.str()->upper(str()->random(5)),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'regulatory_source' => 'YAW TR-010 checklist tenancy verification',
        'regulatory_source_version' => 'TR-010',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Phase 2 tenant-aware test fixture.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function checklistMission(array $overrides = [], ?UasOperator $operator = null): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-CHK-001',
        'purpose' => 'Pre-flight checklist proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Checklist test range',
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator?->id,
        'uas_aircraft_id' => null,
        'uas_pilot_id' => null,
        'planned_start_at' => now()->addDay(),
        'planned_end_at' => now()->addDay()->addHour(),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 1.2,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => null,
        'airspace_assessment' => null,
        'approvals' => [],
        'risk_assessment' => [],
        'emergency_arrangements' => null,
        'lifecycle_state' => 'draft',
        'release_gate_state' => 'amber',
        'release_gate_results' => ['state' => 'amber', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CHK-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Pre-flight checklist verification mission.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function checklistUser(array $permissions = ['missions.view', 'missions.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'pre_flight_checker',
        'label' => 'Pre-flight Checker',
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

function passingChecklistResults(?UasChecklistTemplate $template = null, array $overrides = []): array
{
    $template ??= UasChecklistTemplate::query()->where('type', 'pre_flight')->firstOrFail();

    return collect($template->items)->mapWithKeys(fn (array $item): array => [
        $item['key'] => ['result' => $overrides[$item['key']]['result'] ?? 'pass', 'notes' => $overrides[$item['key']]['notes'] ?? null],
    ])->all();
}

it('seeds a versioned pre-flight checklist template for FR-CHK-001', function () {
    $template = UasChecklistTemplate::query()->where('type', 'pre_flight')->firstOrFail();

    expect($template->version)->toBe('FR-CHK-001-v1')
        ->and($template->active)->toBeTrue()
        ->and($template->items)->toHaveCount(15)
        ->and(collect($template->items)->pluck('label'))->toContain('Aircraft condition', 'Permissions')
        ->and($template->regulatory_source)->toContain('FR-CHK-001');
});

it('requires mission update permission to open and record a pre-flight checklist', function () {
    $this->withoutVite();

    $operator = checklistOperator();
    $mission = checklistMission([], $operator);
    $viewer = checklistUser(['missions.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get("/missions/{$mission->id}/pre-flight-checklist")->assertForbidden();
    $this->actingAs($viewer)->post("/missions/{$mission->id}/pre-flight-checklist", ['results' => passingChecklistResults()])->assertForbidden();
});

it('records performer timestamp version results exceptions and audit evidence', function () {
    $operator = checklistOperator();
    $user = checklistUser([], $operator);
    $mission = checklistMission([], $operator);
    $template = UasChecklistTemplate::query()->where('type', 'pre_flight')->firstOrFail();
    $results = passingChecklistResults($template, [
        'weather' => ['result' => 'pass', 'notes' => 'Wind below site limit.'],
    ]);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/pre-flight-checklist", [
            'results' => $results,
            'exceptions' => 'Battery pack B reserved as contingency.',
        ])
        ->assertRedirect(route('missions.show', $mission));

    $checklist = UasMissionChecklist::query()->firstOrFail();

    expect($checklist->uas_mission_id)->toBe($mission->id)
        ->and($checklist->performed_by)->toBe($user->id)
        ->and($checklist->checklist_version)->toBe('FR-CHK-001-v1')
        ->and($checklist->performed_at)->not->toBeNull()
        ->and($checklist->results['weather']['notes'])->toBe('Wind below site limit.')
        ->and($checklist->exceptions)->toBe('Battery pack B reserved as contingency.')
        ->and($checklist->state)->toBe('completed_with_exceptions');

    $audit = UasAuditEntry::query()->where('action', 'mission_checklist.pre_flight.recorded')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasMissionChecklist::class)
        ->and($audit->auditable_id)->toBe($checklist->id)
        ->and($audit->requirement_id)->toBe('FR-CHK-001');
});

it('blocks the checklist state when a required pre-flight item fails', function () {
    $operator = checklistOperator();
    $user = checklistUser([], $operator);
    $mission = checklistMission([], $operator);
    $template = UasChecklistTemplate::query()->where('type', 'pre_flight')->firstOrFail();
    $results = passingChecklistResults($template, [
        'c2_link' => ['result' => 'fail', 'notes' => 'Link quality unstable.'],
    ]);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/pre-flight-checklist", ['results' => $results])
        ->assertRedirect(route('missions.show', $mission));

    $checklist = UasMissionChecklist::query()->firstOrFail();

    expect($checklist->state)->toBe('blocked')
        ->and($checklist->results['c2_link']['result'])->toBe('fail');
});

it('exposes the active pre-flight template and latest checklist on mission screens', function () {
    $this->withoutVite();

    $operator = checklistOperator();
    $user = checklistUser([], $operator);
    $mission = checklistMission([], $operator);

    $this->actingAs($user)
        ->get("/missions/{$mission->id}/pre-flight-checklist")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/checklists/pre-flight')
            ->where('checklist.template.version', 'FR-CHK-001-v1')
            ->has('checklist.template.items', 15)
        );

    $this->actingAs($user)->post("/missions/{$mission->id}/pre-flight-checklist", [
        'results' => passingChecklistResults(),
    ]);

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->where('preFlightChecklist.latest.checklist_version', 'FR-CHK-001-v1')
            ->where('preFlightChecklist.latest.state', 'completed')
        );
});
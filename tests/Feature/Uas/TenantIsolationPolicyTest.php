<?php

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Geography\Application\Actions\AssignMissionToGisProject;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

function tr004Operator(string $name): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => $name,
        'trading_name' => $name,
        'status'=>'active','accountable_manager'=>'Test Manager','responsible_person_flight_operations'=>'Test Flight Ops','responsible_person_aircraft'=>'Test Aircraft','regulatory_source'=>'Tenancy verification','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Tenant verification','responsible_role'=>'Accountable Manager',
    ]);
}

function tr004User(UasOperator $operator, string $role = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();
    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operator->id,
        'user_id' => $user->id,
        'membership_role' => $role,
        'status' => UasOperatorMembership::STATUS_ACTIVE,
    ]);
    return $user;
}

it('denies cross tenant aircraft and mission policy access', function () {
    $alpha = tr004Operator('TR004 Alpha');
    $bravo = tr004Operator('TR004 Bravo');
    $user = tr004User($alpha);
    $aircraft = UasAircraft::query()->create(['registration' => 'ZT-TR004', 'manufacturer' => 'Test', 'model' => 'Test', 'serial_number' => 'TR004', 'operational_status' => 'serviceable']);
    $bravo->aircraft()->attach($aircraft->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $mission = UasMission::query()->create([
        'mission_number' => 'MIS-TR004-B',
        'uas_operator_id' => $bravo->id,
        'purpose' => 'Isolation verification',
        'location' => 'Test',
        'lifecycle_state'=>MissionLifecycleState::Draft,'regulatory_source'=>'TR tenancy verification','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Tenant isolation verification','responsible_role'=>'Operations Manager','created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    expect($user->can('view', $aircraft))->toBeFalse()
        ->and($user->can('view', $mission))->toBeFalse()
        ->and($user->can('update', $mission))->toBeFalse();
});

it('suspended membership immediately loses resource access', function () {
    $operator = tr004Operator('TR004 Suspend');
    $user = tr004User($operator);
    $aircraft = UasAircraft::query()->create(['registration' => 'ZT-SUSP', 'manufacturer' => 'Test', 'model' => 'Test', 'serial_number' => 'SUSP', 'operational_status' => 'serviceable']);
    $operator->aircraft()->attach($aircraft->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);

    expect($user->can('view', $aircraft))->toBeTrue();

    $user->operatorMemberships()->update(['status' => UasOperatorMembership::STATUS_SUSPENDED]);

    expect($user->fresh()->can('view', $aircraft))->toBeFalse();
});

it('blocks linking one GIS project across operator tenants', function () {
    $alpha = tr004Operator('TR004 GIS Alpha');
    $bravo = tr004Operator('TR004 GIS Bravo');
    $user = tr004User($alpha);
    UasOperatorMembership::query()->create(['uas_operator_id'=>$bravo->id,'user_id'=>$user->id,'membership_role'=>UasOperatorMembership::ROLE_OPERATIONS_MANAGER,'status'=>'active']);

    $project = UasGisProject::query()->create([
        'project_code' => 'GIS-TR004',
        'name' => 'Tenant isolation',
        'project_type' => 'mapping',
        'area_name' => 'Test',
        'lifecycle_state' => 'planning',
        'responsible_role' => 'operations_manager',
        'source_reference' => 'TR-004',
        'source_version' => 'v1',
        'evidence_required' => 'mission_evidence',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $missionA = UasMission::query()->create(['mission_number'=>'GIS-A','uas_operator_id'=>$alpha->id,'purpose'=>'A','location'=>'A','lifecycle_state'=>MissionLifecycleState::Draft,'regulatory_source'=>'TR tenancy verification','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Tenant isolation verification','responsible_role'=>'Operations Manager','created_by'=>$user->id,'updated_by'=>$user->id]);
    $missionB = UasMission::query()->create(['mission_number'=>'GIS-B','uas_operator_id'=>$bravo->id,'purpose'=>'B','location'=>'B','lifecycle_state'=>MissionLifecycleState::Draft,'regulatory_source'=>'TR tenancy verification','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Tenant isolation verification','responsible_role'=>'Operations Manager','created_by'=>$user->id,'updated_by'=>$user->id]);

    $project->projectMissions();
    app(AssignMissionToGisProject::class)->execute($project, [
        'uas_mission_id'=>$missionA->id,'mapping_objective'=>'A','capture_plan'=>'A','expected_outputs'=>[],'field_verification_required'=>false,
    ], $user);

    expect(fn () => app(AssignMissionToGisProject::class)->execute($project, [
        'uas_mission_id'=>$missionB->id,'mapping_objective'=>'B','capture_plan'=>'B','expected_outputs'=>[],'field_verification_required'=>false,
    ], $user))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

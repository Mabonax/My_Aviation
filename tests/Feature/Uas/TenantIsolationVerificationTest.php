<?php

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function tr010Operator(string $name): UasOperator {
    return UasOperator::query()->create(['legal_entity'=>$name,'trading_name'=>$name,'status'=>'active','accountable_manager'=>'TR010 Manager','responsible_person_flight_operations'=>'TR010 Flight Ops','responsible_person_aircraft'=>'TR010 Aircraft','regulatory_source'=>'TR-010','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Isolation verification','responsible_role'=>'Accountable Manager']);
}
function tr010Member(UasOperator $operator, string $role='remote_pilot'): array {
    $user=User::factory()->create();
    $membership=UasOperatorMembership::query()->create(['uas_operator_id'=>$operator->id,'user_id'=>$user->id,'membership_role'=>$role,'status'=>'active','source'=>'admin']);
    return [$user,$membership];
}
function tr010Aircraft(UasOperator $operator, string $registration): UasAircraft {
    $aircraft=UasAircraft::query()->create(['registration'=>$registration,'manufacturer'=>'YAW Test','model'=>'Isolation','serial_number'=>$registration.'-SN','operational_status'=>'serviceable']);
    $operator->aircraft()->attach($aircraft->id,['assignment_role'=>'operated_aircraft','status'=>'active']);
    return $aircraft;
}
function tr010Mission(UasOperator $operator, User $creator, string $number): UasMission {
    return UasMission::query()->create(['mission_number'=>$number,'uas_operator_id'=>$operator->id,'purpose'=>'Isolation verification','location'=>'Test','lifecycle_state'=>MissionLifecycleState::Draft,'regulatory_source'=>'TR tenancy verification','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Tenant isolation verification','responsible_role'=>'Operations Manager','created_by'=>$creator->id,'updated_by'=>$creator->id]);
}

it('rejects a forged API operator header and hides direct foreign mission IDs', function () {
    $alpha=tr010Operator('TR010 Alpha'); $bravo=tr010Operator('TR010 Bravo');
    [$user]=tr010Member($alpha);
    $foreign=tr010Mission($bravo,$user,'TR010-BRAVO');
    Sanctum::actingAs($user);

    $this->withHeader('X-YAW-Operator',(string)$bravo->id)->getJson('/api/v1/aircraft')->assertForbidden();
    $this->withHeader('X-YAW-Operator',(string)$alpha->id)->getJson('/api/v1/missions/'.$foreign->id)->assertNotFound();
});

it('isolates aircraft lists while switching between two legitimate memberships', function () {
    $alpha=tr010Operator('TR010 Switch Alpha'); $bravo=tr010Operator('TR010 Switch Bravo');
    [$user]=tr010Member($alpha);
    UasOperatorMembership::query()->create(['uas_operator_id'=>$bravo->id,'user_id'=>$user->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin']);
    tr010Aircraft($alpha,'ZT-TR010-A'); tr010Aircraft($bravo,'ZT-TR010-B');
    Sanctum::actingAs($user);

    $this->withHeader('X-YAW-Operator',(string)$alpha->id)->getJson('/api/v1/aircraft')->assertOk()->assertJsonFragment(['registration'=>'ZT-TR010-A'])->assertJsonMissing(['registration'=>'ZT-TR010-B']);
    $this->withHeader('X-YAW-Operator',(string)$bravo->id)->getJson('/api/v1/aircraft')->assertOk()->assertJsonFragment(['registration'=>'ZT-TR010-B'])->assertJsonMissing(['registration'=>'ZT-TR010-A']);
});

it('invalidates API and web workspace access immediately after suspension', function () {
    $operator=tr010Operator('TR010 Suspension');
    [$user,$membership]=tr010Member($operator);
    tr010Aircraft($operator,'ZT-TR010-S');
    Sanctum::actingAs($user);

    $this->withHeader('X-YAW-Operator',(string)$operator->id)->getJson('/api/v1/aircraft')->assertOk();
    $membership->update(['status'=>'suspended']);
    $this->withHeader('X-YAW-Operator',(string)$operator->id)->getJson('/api/v1/aircraft')->assertForbidden();

    $this->actingAs($user)->withSession(['yaw_operator_id'=>$operator->id])->get('/dashboard')
        ->assertInertia(fn($page)=>$page->where('operatorWorkspace.active_operator',null));
});

it('does not allow ordinary legacy permissions to become cross tenant authority', function () {
    $alpha=tr010Operator('TR010 Permission Alpha'); $bravo=tr010Operator('TR010 Permission Bravo');
    [$user]=tr010Member($alpha);
    $aircraft=tr010Aircraft($bravo,'ZT-TR010-P');
    expect($user->hasPlatformAuthority('operators.view'))->toBeFalse()
        ->and($user->can('view',$aircraft))->toBeFalse();
});

it('permits explicit platform authority without converting membership permissions into platform authority', function () {
    $alpha=tr010Operator('TR010 Platform Alpha'); $bravo=tr010Operator('TR010 Platform Bravo');
    $admin=User::factory()->create(['role'=>'super_admin']);
    $missionA=tr010Mission($alpha,$admin,'TR010-PA');
    $missionB=tr010Mission($bravo,$admin,'TR010-PB');

    expect($admin->can('view',$missionA))->toBeTrue()->and($admin->can('view',$missionB))->toBeTrue();
});

it('requires explicit tenant selection for multi operator operational APIs', function () {
    $a=tr010Operator('TR010 Required A'); $b=tr010Operator('TR010 Required B');
    [$user]=tr010Member($a);
    UasOperatorMembership::query()->create(['uas_operator_id'=>$b->id,'user_id'=>$user->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin']);
    Sanctum::actingAs($user);

    foreach(['/api/v1/aircraft','/api/v1/missions','/api/v1/defects','/api/v1/batteries','/api/v1/gis-projects','/api/v1/compliance/findings','/api/v1/evidence-documents'] as $uri) {
        $this->getJson($uri)->assertStatus(409);
    }
});

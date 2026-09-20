<?php

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;

function tr009Operator(string $name): UasOperator {
    return UasOperator::query()->create([
        'legal_entity'=>$name,
        'trading_name'=>$name,
        'status'=>'active','accountable_manager'=>'Test Manager','responsible_person_flight_operations'=>'Test Flight Ops','responsible_person_aircraft'=>'Test Aircraft','regulatory_source'=>'Tenancy verification','regulatory_source_version'=>'v1','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Tenant verification','responsible_role'=>'Accountable Manager',
    ]);
}

it('records the selected operator on audit evidence', function () {
    $user=User::factory()->create();
    $operator=tr009Operator('TR009 Alpha');
    UasOperatorMembership::query()->create([
        'uas_operator_id'=>$operator->id,'user_id'=>$user->id,
        'membership_role'=>'operator_admin','status'=>'active' ,'source'=>'admin',
    ]);
    $this->actingAs($user)->withSession(['yaw_operator_id'=>$operator->id]);

    app(RecordAuditEntry::class)->execute(new AuditEntryData(
        actor:$user,auditable:$operator,action:'tr009.test',
        requirementId:'TR-009',regulatorySource:null,previousValues:null,newValues:['ok'=>true],
    ));

    $this->assertDatabaseHas('uas_audit_entries',[
        'action'=>'tr009.test','uas_operator_id'=>$operator->id,
        'operator_context_source'=>'auditable',
    ]);
});

it('does not guess a tenant for an aircraft assigned to multiple operators without active context', function () {
    $aircraft=UasAircraft::query()->create(['registration'=>'ZT-TR009','manufacturer'=>'YAW Test','model'=>'Audit','serial_number'=>'TR009-SN','operational_status'=>'serviceable']);
    $a=tr009Operator('TR009 Multi A'); $b=tr009Operator('TR009 Multi B');
    $aircraft->operators()->attach($a->id,['status'=>'active']);
    $aircraft->operators()->attach($b->id,['status'=>'active']);

    app(RecordAuditEntry::class)->execute(new AuditEntryData(
        actor:null,auditable:$aircraft,action:'tr009.ambiguous',
        requirementId:'TR-009',regulatorySource:null,previousValues:null,newValues:null,
    ));

    $this->assertDatabaseHas('uas_audit_entries',['action'=>'tr009.ambiguous','uas_operator_id'=>null]);
});

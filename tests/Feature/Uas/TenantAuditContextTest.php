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
        'operator_code'=>strtoupper(substr(md5($name),0,8)),
        'status'=>'active',
    ]);
}

it('records the selected operator on audit evidence', function () {
    $user=User::factory()->create();
    $operator=tr009Operator('TR009 Alpha');
    UasOperatorMembership::query()->create([
        'uas_operator_id'=>$operator->id,'user_id'=>$user->id,
        'membership_role'=>'operator_admin','status'=>'active','source'=>'admin',
    ]);
    $this->actingAs($user)->withSession(['yaw_operator_id'=>$operator->id]);

    app(RecordAuditEntry::class)->execute(new AuditEntryData(
        actor:$user,auditable:$operator,action:'tr009.test',
        requirementId:'TR-009',regulatorySource:null,previousValues:null,newValues:['ok'=>true],
    ));

    $this->assertDatabaseHas('uas_audit_entries',[
        'action'=>'tr009.test','uas_operator_id'=>$operator->id,
        'operator_context_source'=>'web_session',
    ]);
});

it('does not guess a tenant for an aircraft assigned to multiple operators without active context', function () {
    $aircraft=UasAircraft::factory()->create();
    $a=tr009Operator('TR009 Multi A'); $b=tr009Operator('TR009 Multi B');
    $aircraft->operators()->attach($a->id,['status'=>'active']);
    $aircraft->operators()->attach($b->id,['status'=>'active']);

    app(RecordAuditEntry::class)->execute(new AuditEntryData(
        actor:null,auditable:$aircraft,action:'tr009.ambiguous',
        requirementId:'TR-009',regulatorySource:null,previousValues:null,newValues:null,
    ));

    $this->assertDatabaseHas('uas_audit_entries',['action'=>'tr009.ambiguous','uas_operator_id'=>null]);
});

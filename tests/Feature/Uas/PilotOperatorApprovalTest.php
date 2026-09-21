<?php
use App\Domains\Uas\Operators\Application\Actions\ApprovePilotForOperator;
use App\Domains\Uas\Operators\Application\Services\PilotOperatorApproval;
use App\Domains\Uas\Operators\Domain\Models\{UasOperator,UasOperatorMembership};
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;
use App\Domains\Uas\Authorization\Domain\Models\UasRole;
use Illuminate\Validation\ValidationException;
function tr006Manager():User{
 $u=User::factory()->create();
 $r=UasRole::query()->create(['name'=>'TR006 Platform Manager '.str()->random(6),'slug'=>'tr006-platform-manager-'.str()->lower(str()->random(6)),'permissions'=>['platform.super_admin']]);
 $u->uasRoles()->attach($r);
 return $u;
}
function tr006Operator():UasOperator{return UasOperator::query()->create(['legal_entity'=>'TR006 Air','trading_name'=>'TR006','operator_code'=>'TR006','status'=>'active','accountable_manager'=>'TR006 Accountable Manager','responsible_person_flight_operations'=>'TR006 Flight Operations','responsible_person_aircraft'=>'TR006 Aircraft','safety_manager'=>'TR006 Safety Manager','security_coordinator'=>'TR006 Security Coordinator','regulatory_source'=>'YAW TR-006 verification','regulatory_source_version'=>'TR-006','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Pilot/operator approval verification.','responsible_role'=>'Accountable Manager']);}
it('requires active membership before pilot operational approval',function(){
 $op=tr006Operator();$user=User::factory()->create();$actor=tr006Manager();$pilot=UasPilot::query()->create(['user_id'=>$user->id,'regulatory_source'=>'Civil Aviation Regulations Part 71; YAW TR-006 verification','regulatory_source_version'=>'TR-006','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Pilot/operator approval verification.','responsible_role'=>'Compliance Manager','first_name'=>'Test','last_name'=>'Pilot','status'=>'active']);
 expect(fn()=>app(ApprovePilotForOperator::class)->execute($op,$pilot,'remote_pilot',$actor))->toThrow(ValidationException::class);
});
it('approves only valid active member pilots and resolves validity window',function(){
 $op=tr006Operator();$user=User::factory()->create();$actor=tr006Manager();$pilot=UasPilot::query()->create(['user_id'=>$user->id,'regulatory_source'=>'Civil Aviation Regulations Part 71; YAW TR-006 verification','regulatory_source_version'=>'TR-006','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Pilot/operator approval verification.','responsible_role'=>'Compliance Manager','first_name'=>'Valid','last_name'=>'Pilot','status'=>'active']);
 UasOperatorMembership::query()->create(['uas_operator_id'=>$op->id,'user_id'=>$user->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin','activated_at'=>now()]);
 $a=app(ApprovePilotForOperator::class)->execute($op,$pilot,'remote_pilot',$actor,today()->toDateString(),today()->addMonth()->toDateString());
 expect(app(PilotOperatorApproval::class)->isApproved($op->id,$pilot))->toBeTrue()->and($a->uas_operator_membership_id)->not->toBeNull();
});
it('suspension and ended membership prevent operational approval resolution',function(){
 $op=tr006Operator();$user=User::factory()->create();$actor=tr006Manager();$pilot=UasPilot::query()->create(['user_id'=>$user->id,'regulatory_source'=>'Civil Aviation Regulations Part 71; YAW TR-006 verification','regulatory_source_version'=>'TR-006','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Pilot/operator approval verification.','responsible_role'=>'Compliance Manager','first_name'=>'Suspend','last_name'=>'Pilot','status'=>'active']);
 $m=UasOperatorMembership::query()->create(['uas_operator_id'=>$op->id,'user_id'=>$user->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin','activated_at'=>now()]);
 $a=app(ApprovePilotForOperator::class)->execute($op,$pilot,'remote_pilot',$actor);
 app(ApprovePilotForOperator::class)->suspend($a,$actor);
 expect(app(PilotOperatorApproval::class)->isApproved($op->id,$pilot))->toBeFalse();
 $a=app(ApprovePilotForOperator::class)->reinstate($a,$actor); expect(app(PilotOperatorApproval::class)->isApproved($op->id,$pilot))->toBeTrue();
 $m->update(['status'=>'ended','left_at'=>now()]); expect(app(PilotOperatorApproval::class)->isApproved($op->id,$pilot))->toBeFalse();
});


it('rejects approval lifecycle mutations from an actor without operator management authority', function () {
 $op=tr006Operator(); $user=User::factory()->create(); $actor=User::factory()->create();
 $pilot=UasPilot::query()->create(['user_id'=>$user->id,'regulatory_source'=>'YAW TR-006 verification','regulatory_source_version'=>'TR-006','regulatory_effective_date'=>'2026-09-20','regulatory_applicability'=>'Pilot/operator approval verification.','responsible_role'=>'Compliance Manager','first_name'=>'Unauthorized','last_name'=>'Pilot','status'=>'active']);
 UasOperatorMembership::query()->create(['uas_operator_id'=>$op->id,'user_id'=>$user->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin','activated_at'=>now()]);
 expect(fn()=>app(ApprovePilotForOperator::class)->execute($op,$pilot,'remote_pilot',$actor))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

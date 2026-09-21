<?php
namespace App\Domains\Uas\Operators\Application\Actions;
use App\Domains\Uas\Operators\Domain\Models\{UasOperator,UasOperatorMembership,UasOperatorPilot};
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
class ApprovePilotForOperator
{
 public function __construct(private readonly RecordAuditEntry $audit){}
 public function execute(UasOperator $operator,UasPilot $pilot,string $role,User $actor,?string $from=null,?string $until=null,?string $notes=null): UasOperatorPilot {
  Gate::forUser($actor)->authorize('manageMemberships', $operator);
  $membership=UasOperatorMembership::query()->where('uas_operator_id',$operator->id)->where('user_id',$pilot->user_id)->where('status','active')->first();
  if(!$membership) throw ValidationException::withMessages(['pilot'=>'The pilot must have an active membership in this operator before operational approval.']);
  if($until && $from && $until<$from) throw ValidationException::withMessages(['approved_until'=>'Approval end date must be on or after the start date.']);
  return DB::transaction(function()use($operator,$pilot,$role,$actor,$from,$until,$notes,$membership){
   $assignment=UasOperatorPilot::query()->updateOrCreate(['uas_operator_id'=>$operator->id,'uas_pilot_id'=>$pilot->id],[
    'uas_operator_membership_id'=>$membership->id,'assignment_role'=>$role,'status'=>'active','approved_from'=>$from??now()->toDateString(),
    'approved_until'=>$until,'approved_at'=>now(),'approved_by'=>$actor->id,'suspended_at'=>null,'ended_at'=>null,'notes'=>$notes,'created_by'=>$actor->id,
   ]);
   $this->audit->execute(new AuditEntryData($actor,$assignment,'pilot.operator_approved','TR-006',$operator->regulatory_source,null,$assignment->getAttributes()));
   return $assignment;
  });
 }
 public function suspend(UasOperatorPilot $assignment,User $actor): UasOperatorPilot { Gate::forUser($actor)->authorize('manageMemberships', $assignment->operator); return $this->transition($assignment,'suspended',$actor); }
 public function reinstate(UasOperatorPilot $assignment,User $actor): UasOperatorPilot {
  Gate::forUser($actor)->authorize('manageMemberships', $assignment->operator);
  if(!$assignment->membership || $assignment->membership->status!=='active') throw ValidationException::withMessages(['membership'=>'Pilot membership must be active before approval can be reinstated.']);
  return $this->transition($assignment,'active',$actor);
 }
 public function end(UasOperatorPilot $assignment,User $actor): UasOperatorPilot { Gate::forUser($actor)->authorize('manageMemberships', $assignment->operator); return $this->transition($assignment,'ended',$actor); }
 private function transition(UasOperatorPilot $a,string $status,User $actor): UasOperatorPilot {
  return DB::transaction(function()use($a,$status,$actor){$previous=$a->getAttributes();$a->forceFill(['status'=>$status,'suspended_at'=>$status==='suspended'?now():null,'ended_at'=>$status==='ended'?now():null])->save();
   $this->audit->execute(new AuditEntryData($actor,$a,'pilot.operator_'.$status,'TR-006',$a->operator->regulatory_source,$previous,$a->getAttributes()));return $a->refresh();});
 }
}
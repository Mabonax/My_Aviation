<?php
namespace App\Domains\Uas\Operators\Application\Services;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorPilot;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Carbon\CarbonInterface;
class PilotOperatorApproval
{
 public function activeAssignment(int $operatorId, UasPilot|int $pilot, ?CarbonInterface $on=null): ?UasOperatorPilot {
  $pilotId=$pilot instanceof UasPilot ? $pilot->id : $pilot; $date=($on??now())->toDateString();
  return UasOperatorPilot::query()->where('uas_operator_id',$operatorId)->where('uas_pilot_id',$pilotId)->where('status',UasOperatorPilot::STATUS_ACTIVE)
   ->where(fn($q)=>$q->whereNull('approved_from')->orWhereDate('approved_from','<=',$date))
   ->where(fn($q)=>$q->whereNull('approved_until')->orWhereDate('approved_until','>=',$date))
   ->whereHas('membership',fn($q)=>$q->where('status','active'))->first();
 }
 public function isApproved(int $operatorId, UasPilot|int $pilot, ?CarbonInterface $on=null): bool { return $this->activeAssignment($operatorId,$pilot,$on)!==null; }
}
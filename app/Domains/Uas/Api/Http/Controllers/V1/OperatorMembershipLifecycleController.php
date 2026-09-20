<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Support\ApiResponse;
use App\Domains\Uas\Operators\Application\Actions\OperatorMembershipLifecycle;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperatorMembershipLifecycleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items=$request->user()->operatorMemberships()->with('operator')->latest()->get()->map(fn($m)=>$this->present($m));
        return ApiResponse::success($items);
    }

    public function invite(Request $request, UasOperator $operator, OperatorMembershipLifecycle $lifecycle, CurrentOperatorContext $context): JsonResponse
    {
        abort_unless($context->canManageOperator($request->user(),$operator),403);
        $data=$request->validate(['user_id'=>['required','integer','exists:users,id'],'membership_role'=>['required',Rule::in(array_keys(UasOperatorMembership::roles()))],'message'=>['nullable','string','max:1000']]);
        $membership=$lifecycle->invite($operator,\App\Models\User::query()->findOrFail($data['user_id']),$data['membership_role'],$request->user(),$data['message']??null);
        return ApiResponse::success($this->present($membership),201);
    }

    public function requestJoin(Request $request, UasOperator $operator, OperatorMembershipLifecycle $lifecycle): JsonResponse
    {
        $data=$request->validate(['membership_role'=>['required',Rule::in(array_keys(UasOperatorMembership::roles()))],'message'=>['nullable','string','max:1000']]);
        $membership=$lifecycle->request($operator,$request->user(),$data['membership_role'],$data['message']??null);
        return ApiResponse::success($this->present($membership),201);
    }

    public function transition(Request $request, UasOperatorMembership $membership, OperatorMembershipLifecycle $lifecycle, CurrentOperatorContext $context): JsonResponse
    {
        $data=$request->validate(['action'=>['required',Rule::in(['accept','decline','approve','reject','suspend','reinstate','end'])]]);
        $self=in_array($data['action'],['accept','decline'],true);
        if (!$self) abort_unless($context->canManageOperator($request->user(),$membership->uas_operator_id),403);
        $membership=match($data['action']){
            'accept'=>$lifecycle->acceptInvitation($membership,$request->user()),
            'decline'=>$lifecycle->declineInvitation($membership,$request->user()),
            'approve'=>$lifecycle->approveRequest($membership,$request->user()),
            'reject'=>$lifecycle->rejectRequest($membership,$request->user()),
            'suspend'=>$lifecycle->suspend($membership,$request->user()),
            'reinstate'=>$lifecycle->reinstate($membership,$request->user()),
            'end'=>$lifecycle->end($membership,$request->user()),
        };
        return ApiResponse::success($this->present($membership));
    }

    private function present(UasOperatorMembership $m): array
    {
        return ['id'=>$m->id,'operator'=>['id'=>$m->operator->id,'name'=>$m->operator->trading_name ?: $m->operator->legal_entity],'role'=>$m->membership_role,'status'=>$m->status,'source'=>$m->source,'message'=>$m->message,'invited_at'=>$m->invited_at?->toISOString(),'activated_at'=>$m->activated_at?->toISOString(),'responded_at'=>$m->responded_at?->toISOString(),'left_at'=>$m->left_at?->toISOString()];
    }
}

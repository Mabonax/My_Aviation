<?php
namespace App\Domains\Uas\Operators\Http\Controllers;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class OperatorWorkspaceController extends Controller
{
 public function select(Request $request, CurrentOperatorContext $context): RedirectResponse {
  $data=$request->validate(['operator_id'=>['required','integer','exists:uas_operators,id']]);
  abort_unless($context->canAccessOperator($request->user(),(int)$data['operator_id']),403);
  $request->session()->put('yaw_operator_id',(int)$data['operator_id']);
  return back();
 }
 public function clear(Request $request): RedirectResponse { $request->session()->forget('yaw_operator_id'); return back(); }
}
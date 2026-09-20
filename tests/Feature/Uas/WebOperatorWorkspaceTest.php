<?php
use App\Domains\Uas\Operators\Domain\Models\{UasOperator,UasOperatorMembership};
use App\Models\User;
function tr007Op(string $name):UasOperator{return UasOperator::query()->create(['legal_entity'=>$name,'trading_name'=>$name,'operator_code'=>strtoupper(substr(md5($name),0,8)),'status'=>'active']);}
it('allows a member to select an accessible web operator workspace',function(){
 $u=User::factory()->create();$a=tr007Op('TR007 Alpha');$b=tr007Op('TR007 Bravo');
 foreach([$a,$b] as $op) UasOperatorMembership::query()->create(['uas_operator_id'=>$op->id,'user_id'=>$u->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin']);
 $this->actingAs($u)->post('/operator-workspace',['operator_id'=>$b->id])->assertRedirect();
 expect(session('yaw_operator_id'))->toBe($b->id);
 $this->actingAs($u)->get('/dashboard')->assertInertia(fn($page)=>$page->where('operatorWorkspace.active_operator.id',$b->id)->where('operatorWorkspace.active_operator.name','TR007 Bravo'));
});
it('blocks selecting an operator outside the users tenancy',function(){
 $u=User::factory()->create();$a=tr007Op('TR007 Member');$b=tr007Op('TR007 Outside');
 UasOperatorMembership::query()->create(['uas_operator_id'=>$a->id,'user_id'=>$u->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin']);
 $this->actingAs($u)->post('/operator-workspace',['operator_id'=>$b->id])->assertForbidden();
});
it('requires explicit workspace selection for multiple active memberships',function(){
 $u=User::factory()->create();foreach([tr007Op('TR007 One'),tr007Op('TR007 Two')] as $op) UasOperatorMembership::query()->create(['uas_operator_id'=>$op->id,'user_id'=>$u->id,'membership_role'=>'remote_pilot','status'=>'active','source'=>'admin']);
 $this->actingAs($u)->get('/dashboard')->assertInertia(fn($page)=>$page->where('operatorWorkspace.active_operator',null)->where('operatorWorkspace.requires_selection',true));
});

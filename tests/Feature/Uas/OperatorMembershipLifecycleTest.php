<?php

use App\Domains\Uas\Operators\Application\Actions\OperatorMembershipLifecycle;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function tr005Operator(string $name): UasOperator {
    return UasOperator::query()->create([
        'legal_entity' => $name,
        'trading_name' => $name,
        'operator_code' => strtoupper(substr(md5($name), 0, 8)),
        'status' => 'active',
        'accountable_manager' => 'TR-005 Accountable Manager',
        'responsible_person_flight_operations' => 'TR-005 Flight Operations',
        'responsible_person_aircraft' => 'TR-005 Aircraft Lead',
        'regulatory_source' => 'TR-005 operator membership lifecycle fixture',
        'regulatory_source_version' => 'v1',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Operator membership lifecycle verification.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

it('supports invitation acceptance and decline by the invited user', function () {
    $operator=tr005Operator('TR005 Invite');
    $manager=User::factory()->create();
    $pilot=User::factory()->create();
    $service=app(OperatorMembershipLifecycle::class);

    $invite=$service->invite($operator,$pilot,UasOperatorMembership::ROLE_REMOTE_PILOT,$manager,'Join our flight team');
    expect($invite->status)->toBe('pending')->and($invite->source)->toBe('invitation');
    $accepted=$service->acceptInvitation($invite,$pilot);
    expect($accepted->status)->toBe('active')->and($accepted->activated_at)->not->toBeNull();

    $pilot2=User::factory()->create();
    $declined=$service->declineInvitation($service->invite($operator,$pilot2,UasOperatorMembership::ROLE_REMOTE_PILOT,$manager),$pilot2);
    expect($declined->status)->toBe('ended')->and($declined->left_at)->not->toBeNull();
});

it('supports join request approval and rejection', function () {
    $operator=tr005Operator('TR005 Request');
    $manager=User::factory()->create();
    $pilot=User::factory()->create();
    $service=app(OperatorMembershipLifecycle::class);

    $request=$service->request($operator,$pilot,UasOperatorMembership::ROLE_REMOTE_PILOT,'Request access');
    expect($request->source)->toBe('join_request')->and($request->status)->toBe('pending');
    expect($service->approveRequest($request,$manager)->status)->toBe('active');

    $pilot2=User::factory()->create();
    expect($service->rejectRequest($service->request($operator,$pilot2,UasOperatorMembership::ROLE_REMOTE_PILOT),$manager)->status)->toBe('ended');
});

it('supports suspension reinstatement and termination', function () {
    $operator=tr005Operator('TR005 Lifecycle');
    $manager=User::factory()->create();
    $pilot=User::factory()->create();
    $service=app(OperatorMembershipLifecycle::class);
    $membership=$service->approveRequest($service->request($operator,$pilot,UasOperatorMembership::ROLE_REMOTE_PILOT),$manager);

    $membership=$service->suspend($membership,$manager);
    expect($membership->status)->toBe('suspended');
    $membership=$service->reinstate($membership,$manager);
    expect($membership->status)->toBe('active');
    $membership=$service->end($membership,$manager);
    expect($membership->status)->toBe('ended')->and($membership->left_at)->not->toBeNull();
    expect(fn()=>$service->reinstate($membership,$manager))->toThrow(ValidationException::class);
});

it('prevents duplicate open memberships', function () {
    $operator=tr005Operator('TR005 Duplicate');
    $pilot=User::factory()->create();
    $service=app(OperatorMembershipLifecycle::class);
    $service->request($operator,$pilot,UasOperatorMembership::ROLE_REMOTE_PILOT);
    expect(fn()=>$service->request($operator,$pilot,UasOperatorMembership::ROLE_REMOTE_PILOT))->toThrow(ValidationException::class);
});


it('prevents a self join request from claiming an elevated operator role', function () {
    $operator=tr005Operator('TR005 Escalation');
    $pilot=User::factory()->create();
    $service=app(OperatorMembershipLifecycle::class);

    expect(fn()=>$service->request($operator,$pilot,UasOperatorMembership::ROLE_ADMINISTRATOR))
        ->toThrow(ValidationException::class);

    expect(UasOperatorMembership::query()->where('uas_operator_id',$operator->id)->where('user_id',$pilot->id)->exists())
        ->toBeFalse();
});

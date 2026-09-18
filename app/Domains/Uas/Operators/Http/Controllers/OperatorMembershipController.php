<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\AddOperatorMembership;
use App\Domains\Uas\Operators\Application\Actions\UpdateOperatorMembershipStatus;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Operators\Http\Requests\StoreOperatorMembershipRequest;
use App\Domains\Uas\Operators\Http\Requests\UpdateOperatorMembershipStatusRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class OperatorMembershipController extends Controller
{
    public function store(StoreOperatorMembershipRequest $request, UasOperator $operator, AddOperatorMembership $addMembership): RedirectResponse
    {
        $data = $request->validated();

        $addMembership->execute(
            $operator,
            User::query()->findOrFail($data['user_id']),
            $data['membership_role'],
            $data['status'],
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Operator membership added.');
    }

    public function updateStatus(UpdateOperatorMembershipStatusRequest $request, UasOperatorMembership $membership, UpdateOperatorMembershipStatus $updateStatus): RedirectResponse
    {
        $updateStatus->execute($membership, $request->validated('status'), $request->user(), $request->ip(), $request->userAgent());

        return back()->with('success', 'Operator membership updated.');
    }
}

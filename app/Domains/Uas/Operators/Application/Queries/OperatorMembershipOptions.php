<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;

class OperatorMembershipOptions
{
    public function execute(): array
    {
        return [
            'roles' => UasOperatorMembership::roles(),
            'statuses' => UasOperatorMembership::statuses(),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'label' => "{$user->name} ({$user->email})",
                ])
                ->values()
                ->all(),
        ];
    }
}

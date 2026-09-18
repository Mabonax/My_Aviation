<?php

namespace App\Domains\Uas\Notifications\Application\Queries;

use App\Domains\Uas\Notifications\Domain\Services\NotificationEngine;
use App\Models\User;

class ComplianceNotificationOptions
{
    public function execute(): array
    {
        return [
            'types' => NotificationEngine::TYPES,
            'channels' => NotificationEngine::CHANNELS,
            'priorities' => NotificationEngine::PRIORITIES,
            'statuses' => NotificationEngine::STATUSES,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email'])->map(fn (User $user): array => [
                'id' => $user->id,
                'label' => trim($user->name.' / '.$user->email),
            ])->all(),
        ];
    }
}

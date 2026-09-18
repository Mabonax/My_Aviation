<?php

namespace App\Domains\Uas\Pilots\Domain\Policies;

final class PilotPermissions
{
    public const MANAGE_ANY = 'pilots.view';
    public const CREATE = 'pilots.create';
    public const UPDATE = 'pilots.update';
    public const SELF_SERVICE = 'pilots.self-service';
}

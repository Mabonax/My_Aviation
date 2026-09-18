<?php

namespace App\Domains\Uas\Access\Domain\Contracts;

use App\Domains\Uas\Access\Domain\DTOs\WorkosIdentity;

interface WorkosGateway
{
    public function authorizationUrl(string $state, string $verifier, bool $signUp = false): string;

    public function mobileAuthorizationUrl(string $state, string $challenge, bool $signUp): string;

    public function revokeSession(string $sessionId): void;

    public function authenticate(string $code, string $verifier): WorkosIdentity;

    public function validateSession(string $sealedSession): WorkosIdentity;

    public function logoutUrl(string $sessionId): string;
}

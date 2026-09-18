<?php

namespace App\Domains\Uas\Access\Domain\DTOs;

final readonly class WorkosIdentity
{
    public function __construct(
        public string $id,
        public string $email,
        public bool $emailVerified,
        public string $name,
        public string $sealedSession,
        public string $sessionId,
    ) {}
}

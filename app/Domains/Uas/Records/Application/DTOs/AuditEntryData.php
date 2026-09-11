<?php

namespace App\Domains\Uas\Records\Application\DTOs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final readonly class AuditEntryData
{
    public function __construct(
        public ?User $actor,
        public Model $auditable,
        public string $action,
        public ?string $requirementId,
        public ?string $regulatorySource,
        public ?array $previousValues,
        public ?array $newValues,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public function toAttributes(): array
    {
        return [
            'user_id' => $this->actor?->id,
            'action' => $this->action,
            'auditable_type' => $this->auditable::class,
            'auditable_id' => $this->auditable->getKey(),
            'requirement_id' => $this->requirementId,
            'regulatory_source' => $this->regulatorySource,
            'previous_values' => $this->previousValues,
            'new_values' => $this->newValues,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'occurred_at' => now(),
        ];
    }
}

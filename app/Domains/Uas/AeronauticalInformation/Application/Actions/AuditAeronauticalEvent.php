<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditAeronauticalEvent
{
    public function __construct(private readonly RecordAuditEntry $audit) {}

    public function execute(Model $subject, string $action, array $evidence, ?User $actor = null): void
    {
        $this->audit->execute(new AuditEntryData($actor, $subject, $action, 'FR-AIM-001..009', 'YAW aeronautical information policy AIM-1.0; source provenance retained in evidence', null, $evidence));
    }
}

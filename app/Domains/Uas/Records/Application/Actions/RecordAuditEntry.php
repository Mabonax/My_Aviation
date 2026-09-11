<?php

namespace App\Domains\Uas\Records\Application\Actions;

use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Records\Domain\Contracts\AuditEntryRepositoryInterface;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;

class RecordAuditEntry
{
    public function __construct(private readonly AuditEntryRepositoryInterface $auditEntries) {}

    public function execute(AuditEntryData $data): UasAuditEntry
    {
        return $this->auditEntries->create($data->toAttributes());
    }
}

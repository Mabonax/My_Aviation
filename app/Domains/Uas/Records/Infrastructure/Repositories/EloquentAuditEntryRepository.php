<?php

namespace App\Domains\Uas\Records\Infrastructure\Repositories;

use App\Domains\Uas\Records\Domain\Contracts\AuditEntryRepositoryInterface;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;

class EloquentAuditEntryRepository implements AuditEntryRepositoryInterface
{
    public function create(array $attributes): UasAuditEntry
    {
        return UasAuditEntry::query()->create($attributes);
    }
}

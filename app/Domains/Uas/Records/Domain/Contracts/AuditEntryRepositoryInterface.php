<?php

namespace App\Domains\Uas\Records\Domain\Contracts;

use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;

interface AuditEntryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): UasAuditEntry;
}

<?php

namespace App\Domains\Uas\Records\Application\Actions;

use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Records\Domain\Contracts\AuditEntryRepositoryInterface;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Records\Application\Services\AuditOperatorContext;

class RecordAuditEntry
{
    public function __construct(
        private readonly AuditEntryRepositoryInterface $auditEntries,
        private readonly AuditOperatorContext $operatorContext,
    ) {}

    public function execute(AuditEntryData $data): UasAuditEntry
    {
        $request = app()->bound('request') ? request() : null;
        [$operatorId, $source] = $this->operatorContext->resolve($request, $data->auditable, $data->operatorId);

        return $this->auditEntries->create(array_merge(
            $data->toAttributes(),
            [
                'uas_operator_id' => $operatorId,
                'operator_context_source' => $data->operatorContextSource ?? $source,
            ],
        ));
    }
}

<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRegulatoryFee
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryFee
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): RegulatoryFee {
            $fee = RegulatoryFee::query()->create([
                ...$this->attributes($data),
                'status' => $data['status'] ?? 'active',
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $fee, 'regulatory_fee.created', 'FR-FEE-001', $fee->source, null, $fee->getAttributes(), $ipAddress, $userAgent));

            return $fee;
        });
    }

    private function attributes(array $data): array
    {
        return [
            'regulation_part' => $data['regulation_part'],
            'transaction_code' => $data['transaction_code'],
            'description' => $data['description'],
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? 'ZAR',
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'source' => $data['source'],
            'source_version' => $data['source_version'],
            'verified_at' => $data['verified_at'] ?? null,
        ];
    }
}

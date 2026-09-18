<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupersedeRegulatoryFee
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(RegulatoryFee $previous, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryFee
    {
        return DB::transaction(function () use ($previous, $data, $actor, $ipAddress, $userAgent): RegulatoryFee {
            $previousValues = $previous->getAttributes();
            $previous->update([
                'status' => 'superseded',
                'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
            ]);

            $next = RegulatoryFee::query()->create([
                'previous_fee_id' => $previous->id,
                'regulation_part' => $data['regulation_part'],
                'transaction_code' => $data['transaction_code'],
                'description' => $data['description'],
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? $previous->currency,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'source' => $data['source'],
                'source_version' => $data['source_version'],
                'status' => 'active',
                'verified_at' => $data['verified_at'] ?? null,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $previous, 'regulatory_fee.superseded', 'FR-FEE-001', $previous->source, $previousValues, $previous->getAttributes(), $ipAddress, $userAgent));
            $this->recordAuditEntry->execute(new AuditEntryData($actor, $next, 'regulatory_fee.version_created', 'FR-FEE-001', $next->source, null, $next->getAttributes(), $ipAddress, $userAgent));

            return $next;
        });
    }
}

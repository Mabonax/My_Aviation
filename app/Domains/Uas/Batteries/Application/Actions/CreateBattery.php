<?php

namespace App\Domains\Uas\Batteries\Application\Actions;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Services\BatteryHealthEvaluator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBattery
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Electric UAS battery inventory, health, cycles, charge history and retirement management.',
    ];

    public function __construct(private readonly BatteryHealthEvaluator $health, private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasBattery
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasBattery {
            $battery = UasBattery::query()->create([
                ...$data,
                'charge_history' => $data['charge_history'] ?? null,
                'evidence_references' => $data['evidence_references'] ?? null,
                ...self::TRACEABILITY,
            ]);

            $battery->forceFill(['health_status' => $this->health->status($battery)])->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $battery, 'battery.created', 'FR-BAT-001', self::TRACEABILITY['regulatory_source'], null, $battery->getAttributes(), $ipAddress, $userAgent));

            return $battery;
        });
    }
}
<?php

namespace App\Domains\Uas\Pilots\Application\Actions;

use App\Domains\Uas\Pilots\Application\DTOs\PilotProfileData;
use App\Domains\Uas\Pilots\Domain\Contracts\PilotRepositoryInterface;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePilotProfile
{
    private const REGULATORY_TRACEABILITY = [
        'regulatory_source' => 'Civil Aviation Regulations Part 71; UAS Compliance & Operations Platform FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Remote pilot profile master record for South African UAS operations managed in the VMT platform.',
        'responsible_role' => 'Compliance Manager',
    ];

    public function __construct(
        private readonly PilotRepositoryInterface $pilots,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UasPilot $pilot, PilotProfileData $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasPilot
    {
        return DB::transaction(function () use ($pilot, $data, $actor, $ipAddress, $userAgent): UasPilot {
            $previousValues = $pilot->getOriginal();

            $pilot = $this->pilots->update($pilot, [
                ...$data->toModelAttributes(),
                ...self::REGULATORY_TRACEABILITY,
                'updated_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $pilot,
                action: 'pilot.profile.updated',
                requirementId: 'FR-PIL-001',
                regulatorySource: self::REGULATORY_TRACEABILITY['regulatory_source'],
                previousValues: $previousValues,
                newValues: $pilot->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $pilot;
        });
    }
}

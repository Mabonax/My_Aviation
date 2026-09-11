<?php

namespace App\Domains\Uas\Pilots\Application\Actions;

use App\Domains\Uas\Pilots\Application\DTOs\PilotProfileData;
use App\Domains\Uas\Pilots\Domain\Contracts\PilotRepositoryInterface;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePilotProfile
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

    public function execute(PilotProfileData $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasPilot
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasPilot {
            $pilot = $this->pilots->create([
                ...$data->toModelAttributes(),
                ...self::REGULATORY_TRACEABILITY,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $pilot,
                action: 'pilot.profile.created',
                requirementId: 'FR-PIL-001',
                regulatorySource: self::REGULATORY_TRACEABILITY['regulatory_source'],
                previousValues: null,
                newValues: $pilot->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $pilot;
        });
    }
}

<?php

namespace App\Domains\Uas\Aircraft\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePhysicalAircraft
{
    public function __construct(
        private readonly RecordAuditEntry $recordAuditEntry,
        private readonly InstantiateAircraftPackage $instantiateAircraftPackage,
    ) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasAircraft
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasAircraft {
            $catalogueModel = isset($data['aircraft_model_id'])
                ? UasAircraftModel::query()->with('manufacturer')->find($data['aircraft_model_id'])
                : null;

            $operator = isset($data['uas_operator_id'])
                ? UasOperator::query()->find($data['uas_operator_id'])
                : null;

            $aircraft = UasAircraft::query()->create([
                'aircraft_model_id' => $catalogueModel?->id,
                'registration' => $data['registration'],
                'manufacturer' => $data['manufacturer'] ?? $catalogueModel?->manufacturer?->name ?? 'Custom',
                'model' => $data['model'] ?? $catalogueModel?->model ?? 'Custom',
                'serial_number' => $data['serial_number'],
                'internal_asset_number' => $data['internal_asset_number'] ?? null,
                'aircraft_category' => $data['aircraft_category'] ?? 'uas',
                'owner' => $data['owner'] ?? null,
                'operator' => $operator?->legal_entity ?? $data['operator'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? null,
                'flight_controller_serial' => $data['flight_controller_serial'] ?? null,
                'remote_id_serial' => $data['remote_id_serial'] ?? null,
                'acquisition_date' => $data['acquisition_date'] ?? null,
                'operational_status' => $data['operational_status'] ?? 'pending_registration',
                'onboarding_status' => $data['onboarding_status'] ?? 'onboarding',
                'base_location' => $data['base_location'] ?? null,
                'manual_references' => [],
                'evidence_references' => [],
            ]);

            if ($operator !== null) {
                $operator->aircraft()->syncWithoutDetaching([
                    $aircraft->id => [
                        'assignment_role' => 'operated_aircraft',
                        'status' => 'active',
                        'approved_from' => now()->toDateString(),
                        'created_by' => $actor->id,
                    ],
                ]);
            }

            $packageResults = $this->instantiateAircraftPackage->execute($aircraft, $catalogueModel, $actor, $ipAddress, $userAgent);
            $aircraft->refresh();

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $aircraft,
                action: 'aircraft.onboarded',
                requirementId: 'FR-AIR-001',
                regulatorySource: 'UAS Compliance & Operations Platform FRS FR-AIR-001',
                previousValues: null,
                newValues: [
                    'aircraft_model_id' => $aircraft->aircraft_model_id,
                    'registration' => $aircraft->registration,
                    'serial_number' => $aircraft->serial_number,
                    'uas_operator_id' => $operator?->id,
                    'onboarding_status' => $aircraft->onboarding_status,
                    'package_instantiation' => $packageResults,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $aircraft;
        });
    }
}

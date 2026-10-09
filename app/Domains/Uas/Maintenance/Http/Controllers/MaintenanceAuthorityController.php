<?php

namespace App\Domains\Uas\Maintenance\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Maintenance\Domain\Models\MaintenanceAuthority;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceAuthorityController extends AircraftMaintenanceController
{
    public function store(Request $request, UasAircraft $aircraft, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context, true);
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'evidence_reference' => ['required', 'string', 'max:2000'],
            'authority_confirmed' => ['required', 'accepted'],
            'can_return_to_service' => ['sometimes', 'boolean'],
        ]);
        $authority = DB::transaction(function () use ($data, $operator, $aircraft, $request) {
            UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            if ((int) $data['user_id'] === (int) $request->user()->id) {
                throw ValidationException::withMessages(['user_id' => 'Another operator manager must verify and grant your authority.']);
            }
            $member = UasOperatorMembership::query()->where('uas_operator_id', $operator->id)
                ->where('user_id', $data['user_id'])->where('status', UasOperatorMembership::STATUS_ACTIVE)
                ->lockForUpdate()->first();
            if (! $member) {
                throw ValidationException::withMessages(['user_id' => 'Select an active member of this operator.']);
            }
            $authority = MaintenanceAuthority::query()->create([
                'uas_operator_id' => $operator->id, 'uas_aircraft_id' => $aircraft->id,
                'user_id' => $data['user_id'], 'valid_until' => $data['valid_until'],
                'evidence_reference' => $data['evidence_reference'], 'granted_by' => $request->user()->id,
                'can_return_to_service' => $data['can_return_to_service'] ?? false,
            ]);
            $this->recordAuthority($request, $authority, 'maintenance.authority_granted');
            return $authority;
        });
        return $request->expectsJson() ? ApiResponse::success(['authority' => $authority], 'Maintenance authority recorded.', 201)
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }

    public function revoke(Request $request, UasAircraft $aircraft, MaintenanceAuthority $authority, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context, true);
        abort_unless((int) $authority->uas_operator_id === (int) $operator->id
            && (int) $authority->uas_aircraft_id === (int) $aircraft->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $authority = DB::transaction(function () use ($authority, $aircraft, $request, $data) {
            UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            $authority = MaintenanceAuthority::query()->lockForUpdate()->findOrFail($authority->id);
            if ($authority->revoked_at === null) {
                $authority->forceFill(['revoked_at' => now(), 'revoked_by' => $request->user()->id,
                    'revocation_reason' => $data['reason']])->save();
                $this->recordAuthority($request, $authority, 'maintenance.authority_revoked');
            }
            return $authority;
        });
        return $request->expectsJson() ? ApiResponse::success(['authority' => $authority])
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }

    private function recordAuthority(Request $request, MaintenanceAuthority $authority, string $action): void
    {
        app(RecordAuditEntry::class)->execute(new AuditEntryData(
            actor: $request->user(), auditable: $authority, action: $action,
            requirementId: 'FR-MNT-003', regulatorySource: $authority->evidence_reference,
            previousValues: null, newValues: $authority->toArray(), operatorId: $authority->uas_operator_id,
            operatorContextSource: 'maintenance_workspace',
        ));
    }
}

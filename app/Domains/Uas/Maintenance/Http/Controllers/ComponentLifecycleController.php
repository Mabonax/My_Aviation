<?php

namespace App\Domains\Uas\Maintenance\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftComponent;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ComponentLifecycleController extends AircraftMaintenanceController
{
    public function remove(Request $request, UasAircraft $aircraft, UasAircraftComponent $component, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context);
        abort_unless((int) $component->uas_aircraft_id === (int) $aircraft->id, 404);
        $data = $request->validate($this->evidenceRules());
        $component = DB::transaction(function () use ($aircraft, $component, $request, $data, $operator) {
            UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            $authority = app(\App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority::class)
                ->require($operator->id, $aircraft->id, $request->user()->id);
            $data = [...$data, 'certifying_user_id' => $request->user()->id, 'authority_snapshot' => $authority->toArray()];
            $component = UasAircraftComponent::query()->lockForUpdate()->findOrFail($component->id);
            if ($component->status === 'awaiting_replacement') {
                return $component;
            }
            if ($component->status !== 'active') {
                throw ValidationException::withMessages(['component' => 'Only an active installed component can be removed.']);
            }
            $component->forceFill(['status' => 'awaiting_replacement', 'removed_at' => now(),
                'removed_by' => $request->user()->id, 'removal_evidence' => [...$data, 'operator_id' => $operator->id]])->save();
            $this->recordLifecycle($request, $component, $operator->id, 'component.removed');
            return $component;
        });
        return $request->expectsJson() ? ApiResponse::success(['component' => $this->presentComponent($component, $operator->id)])
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }

    public function replace(Request $request, UasAircraft $aircraft, UasAircraftComponent $component, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context);
        abort_unless((int) $component->uas_aircraft_id === (int) $aircraft->id, 404);
        $data = $request->validate([...$this->evidenceRules(),
            'serial_number' => ['required', 'string', 'max:255'],
            'life_limit_hours' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'life_limit_cycles' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'accumulated_hours' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'accumulated_cycles' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);
        $replacement = DB::transaction(function () use ($aircraft, $component, $request, $data, $operator) {
            UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            $authority = app(\App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority::class)
                ->require($operator->id, $aircraft->id, $request->user()->id);
            $data = [...$data, 'certifying_user_id' => $request->user()->id, 'authority_snapshot' => $authority->toArray()];
            $component = UasAircraftComponent::query()->lockForUpdate()->findOrFail($component->id);
            $existing = UasAircraftComponent::query()->where('replaces_component_id', $component->id)->first();
            if ($existing) {
                return $existing;
            }
            if (! in_array($component->status, ['active', 'awaiting_replacement'], true)) {
                throw ValidationException::withMessages(['component' => 'Select an active component or one awaiting replacement.']);
            }
            $hoursLimit = $data['life_limit_hours'] ?? $component->life_limit_hours;
            $cyclesLimit = $data['life_limit_cycles'] ?? $component->life_limit_cycles;
            if ($component->serial_number === $data['serial_number']
                || $aircraft->components()->where('serial_number', $data['serial_number'])->whereIn('status', ['active', 'awaiting_replacement'])->exists()) {
                throw ValidationException::withMessages(['serial_number' => 'The replacement must have a distinct serial number.']);
            }
            if (($hoursLimit !== null && (float) $data['accumulated_hours'] >= (float) $hoursLimit)
                || ($cyclesLimit !== null && $data['accumulated_cycles'] >= $cyclesLimit)) {
                throw ValidationException::withMessages(['component' => 'A replacement must have remaining component life.']);
            }
            if ($component->removed_at === null) {
                $component->forceFill(['removed_at' => now(), 'removed_by' => $request->user()->id,
                    'removal_evidence' => [...$data, 'operator_id' => $operator->id]])->save();
                $this->recordLifecycle($request, $component, $operator->id, 'component.removed');
            }
            $component->forceFill(['status' => 'removed'])->save();
            $replacement = UasAircraftComponent::query()->create([
                'uas_aircraft_id' => $aircraft->id, 'source_aircraft_model_id' => $component->source_aircraft_model_id,
                'package_item_key' => 'replacement-'.Str::uuid(), 'component_uid' => 'CMP-'.Str::uuid(),
                'component_type' => $component->component_type, 'name' => $component->name,
                'manufacturer' => $component->manufacturer, 'model' => $component->model,
                'serial_number' => $data['serial_number'], 'installed_at' => now(), 'status' => 'active',
                'life_limit_hours' => $hoursLimit, 'life_limit_cycles' => $cyclesLimit,
                'accumulated_hours' => $data['accumulated_hours'], 'accumulated_cycles' => $data['accumulated_cycles'],
                'replaces_component_id' => $component->id, 'installation_evidence' => [...$data, 'installed_by' => $request->user()->id, 'operator_id' => $operator->id],
                'maintenance_baseline' => ['note' => 'Configure replacement requirements from supporting programme evidence.'],
            ]);
            $this->recordLifecycle($request, $replacement, $operator->id, 'component.installed');
            return $replacement;
        });
        return $request->expectsJson() ? ApiResponse::success(['component' => $this->presentComponent($replacement, $operator->id)])
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }

    private function evidenceRules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'], 'technician' => ['required', 'string', 'max:180'],
            'evidence_reference' => ['required', 'string', 'max:2000'],
            'certification' => ['required', 'string', 'max:2000'],
            'change_confirmed' => ['required', 'accepted'],
        ];
    }

    private function presentComponent(UasAircraftComponent $component, int $operatorId): array
    {
        $record = $component->toArray();
        foreach (['removal_evidence', 'installation_evidence'] as $key) {
            if ((int) data_get($record, $key.'.operator_id') !== $operatorId) {
                unset($record[$key]);
            }
        }
        return $record;
    }

    private function recordLifecycle(Request $request, UasAircraftComponent $component, int $operatorId, string $action): void
    {
        app(RecordAuditEntry::class)->execute(new AuditEntryData(
            actor: $request->user(), auditable: $component, action: $action,
            requirementId: 'FR-MNT-003', regulatorySource: 'Operator component change evidence',
            previousValues: null, newValues: $component->toArray(), operatorId: $operatorId,
            operatorContextSource: 'maintenance_workspace',
        ));
    }
}

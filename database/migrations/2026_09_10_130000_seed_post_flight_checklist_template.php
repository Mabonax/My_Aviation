<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('uas_checklist_templates')->updateOrInsert(
            ['type' => 'post_flight', 'version' => 'FR-CHK-002-v1'],
            [
                'name' => 'Post-flight recovery and close-out checklist',
                'effective_date' => '2026-09-10',
                'active' => true,
                'items' => json_encode($this->postFlightItems()),
                'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CHK-002',
                'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
                'regulatory_effective_date' => '2026-09-09',
                'regulatory_applicability' => 'Configurable and versioned post-flight checklist for mission close-out evidence.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('uas_checklist_templates')
            ->where('type', 'post_flight')
            ->where('version', 'FR-CHK-002-v1')
            ->delete();
    }

    private function postFlightItems(): array
    {
        return [
            ['key' => 'aircraft_condition_after_flight', 'label' => 'Aircraft condition after flight', 'required' => true, 'sequence' => 1],
            ['key' => 'propellers_and_motors', 'label' => 'Propellers and motors', 'required' => true, 'sequence' => 2],
            ['key' => 'battery_recovery', 'label' => 'Battery recovery and state', 'required' => true, 'sequence' => 3],
            ['key' => 'rps_controller_condition', 'label' => 'RPS/controller condition', 'required' => true, 'sequence' => 4],
            ['key' => 'payload_and_data', 'label' => 'Payload and captured data', 'required' => true, 'sequence' => 5],
            ['key' => 'flight_log_completed', 'label' => 'Flight log completed', 'required' => true, 'sequence' => 6],
            ['key' => 'crew_debrief', 'label' => 'Crew debrief completed', 'required' => true, 'sequence' => 7],
            ['key' => 'incidents_or_defects', 'label' => 'Incidents or defects recorded', 'required' => true, 'sequence' => 8],
            ['key' => 'site_restored', 'label' => 'Site restored and secured', 'required' => true, 'sequence' => 9],
            ['key' => 'client_handover', 'label' => 'Client handover or close-out notes', 'required' => false, 'sequence' => 10],
        ];
    }
};
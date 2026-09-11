<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_checklist_templates')) {
            Schema::create('uas_checklist_templates', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->string('name');
                $table->string('version');
                $table->date('effective_date')->nullable();
                $table->boolean('active')->default(true);
                $table->json('items');
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date')->nullable();
                $table->text('regulatory_applicability')->nullable();
                $table->timestamps();
                $table->unique(['type', 'version']);
                $table->index(['type', 'active']);
            });
        }

        if (! Schema::hasTable('uas_mission_checklists')) {
            Schema::create('uas_mission_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_mission_id')->constrained('uas_missions')->cascadeOnDelete();
                $table->foreignId('uas_checklist_template_id')->constrained('uas_checklist_templates')->restrictOnDelete();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type');
                $table->string('checklist_version');
                $table->timestamp('performed_at');
                $table->json('results');
                $table->text('exceptions')->nullable();
                $table->string('state')->default('completed');
                $table->timestamps();
                $table->index(['uas_mission_id', 'type']);
                $table->index(['type', 'state']);
            });
        }

        DB::table('uas_checklist_templates')->updateOrInsert(
            ['type' => 'pre_flight', 'version' => 'FR-CHK-001-v1'],
            [
                'name' => 'Pre-flight operational readiness checklist',
                'effective_date' => '2026-09-10',
                'active' => true,
                'items' => json_encode($this->preFlightItems()),
                'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CHK-001',
                'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
                'regulatory_effective_date' => '2026-09-09',
                'regulatory_applicability' => 'Configurable and versioned pre-flight checklist for mission release evidence.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_mission_checklists');
        Schema::dropIfExists('uas_checklist_templates');
    }

    private function preFlightItems(): array
    {
        return collect([
            'Aircraft condition',
            'Propellers',
            'Motors',
            'Battery',
            'RPS/controller',
            'GNSS',
            'C2 link',
            'Firmware/configuration',
            'Payload',
            'Weather',
            'Site security',
            'Emergency landing area',
            'Crew briefing',
            'Third-party/public exposure',
            'Permissions',
        ])->map(fn (string $label, int $index): array => [
            'key' => str($label)->lower()->replace(['/', ' '], '_')->replaceMatches('/[^a-z0-9_]/', '')->toString(),
            'label' => $label,
            'required' => true,
            'sequence' => $index + 1,
        ])->all();
    }
};
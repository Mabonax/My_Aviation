<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_batteries')) {
            Schema::create('uas_batteries', function (Blueprint $table) {
                $table->id();
                $table->string('battery_uid')->unique();
                $table->string('manufacturer');
                $table->string('model')->nullable();
                $table->string('serial_number')->unique();
                $table->foreignId('compatible_uas_aircraft_id')->nullable()->constrained('uas_aircraft')->nullOnDelete();
                $table->unsignedInteger('cycle_count')->default(0);
                $table->unsignedInteger('maximum_cycles')->nullable();
                $table->string('health_status')->default('unknown');
                $table->date('acquisition_date')->nullable();
                $table->date('last_used_at')->nullable();
                $table->text('damage_incidents')->nullable();
                $table->string('retirement_status')->default('active');
                $table->json('charge_history')->nullable();
                $table->json('evidence_references')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date')->nullable();
                $table->text('regulatory_applicability')->nullable();
                $table->timestamps();
                $table->index(['health_status', 'retirement_status']);
                $table->index(['compatible_uas_aircraft_id']);
            });
        }

        if (! Schema::hasTable('uas_mission_battery_usages')) {
            Schema::create('uas_mission_battery_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_mission_id')->constrained('uas_missions')->cascadeOnDelete();
                $table->foreignId('uas_battery_id')->constrained('uas_batteries')->restrictOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('cycles_added')->default(1);
                $table->unsignedInteger('state_of_charge_start')->nullable();
                $table->unsignedInteger('state_of_charge_end')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['uas_mission_id', 'uas_battery_id']);
                $table->index(['uas_battery_id', 'used_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_mission_battery_usages');
        Schema::dropIfExists('uas_batteries');
    }
};
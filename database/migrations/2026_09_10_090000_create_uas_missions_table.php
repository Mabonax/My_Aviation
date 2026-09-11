<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_missions')) {
            Schema::create('uas_missions', function (Blueprint $table) {
                $table->id();
                $table->string('mission_number')->unique();
                $table->string('purpose');
                $table->string('client_project')->nullable();
                $table->string('location');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->json('mission_polygon')->nullable();
                $table->string('operation_category')->default('standard');
                $table->foreignId('uas_aircraft_id')->nullable()->constrained('uas_aircraft')->nullOnDelete();
                $table->foreignId('uas_pilot_id')->nullable()->constrained('uas_pilots')->nullOnDelete();
                $table->json('observers_crew')->nullable();
                $table->timestamp('planned_start_at')->nullable();
                $table->timestamp('planned_end_at')->nullable();
                $table->unsignedInteger('maximum_altitude_ft')->nullable();
                $table->decimal('planned_distance_km', 8, 2)->nullable();
                $table->string('operation_visibility')->default('vlos');
                $table->string('day_night')->default('day');
                $table->text('weather')->nullable();
                $table->text('airspace_assessment')->nullable();
                $table->json('approvals')->nullable();
                $table->json('risk_assessment')->nullable();
                $table->text('emergency_arrangements')->nullable();
                $table->string('lifecycle_state')->default('draft');
                $table->string('release_gate_state')->default('not_evaluated');
                $table->json('release_gate_results')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->text('regulatory_applicability');
                $table->string('responsible_role');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['lifecycle_state', 'planned_start_at']);
                $table->index(['release_gate_state', 'planned_start_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_missions');
    }
};

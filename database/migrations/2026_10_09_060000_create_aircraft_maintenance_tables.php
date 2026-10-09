<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uas_component_flight_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_aircraft_component_id')->constrained('uas_aircraft_components')->restrictOnDelete();
            $table->foreignId('uas_mission_id')->constrained('uas_missions')->restrictOnDelete();
            $table->decimal('flight_hours', 10, 2);
            $table->unsignedInteger('flight_cycles')->default(1);
            $table->timestamp('recorded_at');
            $table->unique(['uas_aircraft_component_id', 'uas_mission_id'], 'comp_mission_usage_unique');
        });
        Schema::create('uas_maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->restrictOnDelete();
            $table->foreignId('uas_aircraft_component_id')->nullable()->constrained('uas_aircraft_components')->restrictOnDelete();
            $table->string('title', 180);
            $table->text('requirement_source');
            $table->date('due_at')->nullable();
            $table->decimal('due_hours', 10, 2)->nullable();
            $table->unsignedInteger('due_cycles')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('completion_evidence')->nullable();
            $table->timestamps();
            $table->index(['uas_aircraft_id', 'completed_at'], 'air_maintenance_open_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_maintenance_tasks');
        Schema::dropIfExists('uas_component_flight_usage');
    }
};

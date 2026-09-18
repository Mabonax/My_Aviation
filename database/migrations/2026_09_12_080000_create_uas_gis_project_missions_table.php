<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uas_gis_project_missions')) {
            return;
        }

        Schema::create('uas_gis_project_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_gis_project_id')->constrained('uas_gis_projects')->cascadeOnDelete();
            $table->foreignId('uas_mission_id')->constrained('uas_missions')->cascadeOnDelete();
            $table->string('mapping_objective');
            $table->text('capture_plan');
            $table->json('expected_outputs');
            $table->string('field_verification_required')->default('required');
            $table->text('evidence_notes')->nullable();
            $table->string('status', 40)->default('planned');
            $table->unsignedBigInteger('assigned_by');
            $table->timestamps();

            $table->unique('uas_mission_id', 'uas_gis_project_mission_unique');
            $table->index(['uas_gis_project_id', 'status'], 'uas_gis_project_mission_status_idx');
            $table->foreign('assigned_by', 'uas_gis_project_mission_assigned_by_fk')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_gis_project_missions');
    }
};

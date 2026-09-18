<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uas_gis_projects')) {
            return;
        }

        Schema::create('uas_gis_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 80)->unique();
            $table->string('name');
            $table->string('project_type', 80);
            $table->string('client_or_stakeholder')->nullable();
            $table->string('area_name');
            $table->string('location_search_query')->nullable();
            $table->decimal('centroid_latitude', 10, 7)->nullable();
            $table->decimal('centroid_longitude', 10, 7)->nullable();
            $table->json('area_boundary')->nullable();
            $table->string('lifecycle_state', 40)->default('plan');
            $table->string('source_reference');
            $table->string('source_version')->nullable();
            $table->text('data_governance_notes')->nullable();
            $table->string('evidence_required');
            $table->string('responsible_role', 120);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            $table->index(['project_type', 'lifecycle_state'], 'uas_gis_project_type_state_idx');
            $table->index(['area_name', 'lifecycle_state'], 'uas_gis_project_area_state_idx');
            $table->foreign('created_by', 'uas_gis_project_created_by_fk')->references('id')->on('users');
            $table->foreign('updated_by', 'uas_gis_project_updated_by_fk')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_gis_projects');
    }
};

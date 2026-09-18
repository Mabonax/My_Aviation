<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_gis_datasets')) {
            Schema::create('uas_gis_datasets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_gis_project_mission_id')->constrained('uas_gis_project_missions')->cascadeOnDelete();
                $table->string('dataset_code', 80)->unique();
                $table->string('title');
                $table->string('dataset_type', 80);
                $table->string('capture_source', 120);
                $table->string('storage_uri');
                $table->string('checksum')->nullable();
                $table->string('coordinate_reference_system', 80)->nullable();
                $table->decimal('resolution_cm', 10, 2)->nullable();
                $table->timestamp('captured_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->string('processing_status', 40)->default('raw');
                $table->string('quality_status', 40)->default('unchecked');
                $table->text('provenance_notes');
                $table->text('evidence_notes')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index(['uas_gis_project_mission_id', 'processing_status'], 'uas_gis_dataset_mission_status_idx');
                $table->index(['dataset_type', 'quality_status'], 'uas_gis_dataset_type_quality_idx');
                $table->foreign('created_by', 'uas_gis_dataset_created_by_fk')->references('id')->on('users');
            });
        }

        if (! Schema::hasTable('uas_gis_spatial_layers')) {
            Schema::create('uas_gis_spatial_layers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_gis_dataset_id')->constrained('uas_gis_datasets')->cascadeOnDelete();
                $table->string('layer_name');
                $table->string('layer_type', 80);
                $table->string('geometry_type', 80);
                $table->string('source_uri')->nullable();
                $table->json('style_metadata')->nullable();
                $table->text('analysis_notes')->nullable();
                $table->string('status', 40)->default('draft');
                $table->timestamps();

                $table->unique(['uas_gis_dataset_id', 'layer_name'], 'uas_gis_layer_dataset_name_unique');
                $table->index(['layer_type', 'status'], 'uas_gis_layer_type_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_gis_spatial_layers');
        Schema::dropIfExists('uas_gis_datasets');
    }
};

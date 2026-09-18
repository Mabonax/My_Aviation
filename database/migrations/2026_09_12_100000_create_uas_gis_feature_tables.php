<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uas_gis_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_gis_spatial_layer_id')->constrained('uas_gis_spatial_layers')->cascadeOnDelete();
            $table->string('feature_code', 80)->unique();
            $table->string('name');
            $table->string('feature_type', 80);
            $table->text('geometry_reference');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('verification_status', 40)->default('unverified');
            $table->text('interpretation_notes');
            $table->text('evidence_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['uas_gis_spatial_layer_id', 'verification_status'], 'uas_gis_feature_layer_status_idx');
            $table->index(['feature_type', 'verification_status'], 'uas_gis_feature_type_status_idx');
        });

        Schema::create('uas_gis_opportunity_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_gis_feature_id')->constrained('uas_gis_features')->cascadeOnDelete();
            $table->string('record_type', 40);
            $table->string('category', 80);
            $table->string('title');
            $table->text('description');
            $table->string('significance', 40)->default('medium');
            $table->text('recommended_action')->nullable();
            $table->string('priority', 40)->default('routine');
            $table->string('status', 40)->default('draft');
            $table->string('evidence_reference', 2048)->nullable();
            $table->date('due_date')->nullable();
            $table->string('responsible_role')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['record_type', 'status'], 'uas_gis_opp_find_type_status_idx');
            $table->index(['category', 'priority'], 'uas_gis_opp_find_cat_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_gis_opportunity_findings');
        Schema::dropIfExists('uas_gis_features');
    }
};

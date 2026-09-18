<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operations_manual_training_requirements')) {
            Schema::create('uas_operations_manual_training_requirements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('manual_revision_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('title');
                $table->string('requirement_type')->default('operator_internal_competency');
                $table->string('training_status')->default('required');
                $table->json('affected_roles')->nullable();
                $table->date('due_date')->nullable();
                $table->string('competency_standard')->nullable();
                $table->text('trigger_reason')->nullable();
                $table->json('evidence_references')->nullable();
                $table->text('notes')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
            });
        }

        Schema::table('uas_operations_manual_training_requirements', function (Blueprint $table) {
            if (! Schema::hasIndex('uas_operations_manual_training_requirements', 'uas_manual_training_status_idx')) {
                $table->index(['manual_revision_id', 'training_status'], 'uas_manual_training_status_idx');
            }

            if (! Schema::hasIndex('uas_operations_manual_training_requirements', 'uas_manual_training_due_idx')) {
                $table->index('due_date', 'uas_manual_training_due_idx');
            }

            if (! Schema::hasIndex('uas_operations_manual_training_requirements', 'uas_manual_training_creator_idx')) {
                $table->index('created_by', 'uas_manual_training_creator_idx');
            }
        });

        Schema::table('uas_operations_manual_training_requirements', function (Blueprint $table) {
            $table->foreign('manual_revision_id', 'uas_manual_training_revision_fk')->references('id')->on('uas_operations_manual_revisions')->cascadeOnDelete();
            $table->foreign('created_by', 'uas_manual_training_creator_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_operations_manual_training_requirements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operations_manual_revisions')) {
            Schema::create('uas_operations_manual_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_operator_id')->constrained('uas_operators')->cascadeOnDelete();
                $table->foreignId('superseded_revision_id')->nullable()->constrained('uas_operations_manual_revisions')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('manual_name');
                $table->string('revision_code');
                $table->date('effective_date')->nullable();
                $table->string('approval_status')->default('draft');
                $table->string('authority_approval_reference')->nullable();
                $table->json('sections')->nullable();
                $table->text('change_summary')->nullable();
                $table->json('evidence_references')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
                $table->unique(['uas_operator_id', 'manual_name', 'revision_code'], 'uas_manual_revision_unique');
                $table->index(['uas_operator_id', 'approval_status'], 'uas_manual_operator_status_idx');
                $table->index('effective_date', 'uas_manual_effective_date_idx');
            });

            return;
        }

        Schema::table('uas_operations_manual_revisions', function (Blueprint $table) {
            if (! Schema::hasIndex('uas_operations_manual_revisions', 'uas_manual_operator_status_idx')) {
                $table->index(['uas_operator_id', 'approval_status'], 'uas_manual_operator_status_idx');
            }

            if (! Schema::hasIndex('uas_operations_manual_revisions', 'uas_manual_effective_date_idx')) {
                $table->index('effective_date', 'uas_manual_effective_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_operations_manual_revisions');
    }
};

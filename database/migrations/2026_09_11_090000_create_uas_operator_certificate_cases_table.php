<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operator_certificate_cases')) {
            Schema::create('uas_operator_certificate_cases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_operator_id')->constrained('uas_operators')->cascadeOnDelete();
                $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('case_number')->unique();
                $table->string('case_type');
                $table->string('status')->default('draft');
                $table->date('deadline_at')->nullable();
                $table->json('evidence_requirements')->nullable();
                $table->json('outstanding_documents')->nullable();
                $table->json('fleet_scope')->nullable();
                $table->json('personnel_scope')->nullable();
                $table->json('ops_spec_scope')->nullable();
                $table->string('operations_manual_revision')->nullable();
                $table->json('fees')->nullable();
                $table->string('submission_status')->default('not_ready');
                $table->json('authority_correspondence')->nullable();
                $table->text('outcome')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
                $table->index(['uas_operator_id', 'case_type']);
                $table->index(['status', 'deadline_at']);
                $table->index('submission_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_operator_certificate_cases');
    }
};
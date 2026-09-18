<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operations_manual_distributions')) {
            Schema::create('uas_operations_manual_distributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manual_revision_id')->constrained('uas_operations_manual_revisions')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recipient_name');
                $table->string('recipient_role');
                $table->string('recipient_email')->nullable();
                $table->string('distribution_channel')->default('manual_register');
                $table->string('distribution_status')->default('required');
                $table->date('required_by')->nullable();
                $table->timestamp('distributed_at')->nullable();
                $table->json('evidence_references')->nullable();
                $table->text('notes')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
                $table->unique(['manual_revision_id', 'recipient_name', 'recipient_role'], 'uas_manual_dist_recipient_unique');
                $table->index(['manual_revision_id', 'distribution_status'], 'uas_manual_dist_status_idx');
                $table->index('required_by', 'uas_manual_dist_required_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_operations_manual_distributions');
    }
};

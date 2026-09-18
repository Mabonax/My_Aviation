<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_evidence_documents')) {
            Schema::create('uas_evidence_documents', function (Blueprint $table) {
                $table->id();
                $table->string('document_uid')->unique();
                $table->foreignId('uas_operator_id')->nullable()->constrained('uas_operators')->nullOnDelete();
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('category', 80);
                $table->string('status', 40)->default('active');
                $table->string('disk', 80)->default('local');
                $table->string('path', 2048);
                $table->string('original_filename');
                $table->string('mime_type', 160)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('checksum_sha256', 64)->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->string('access_level', 40)->default('operator');
                $table->string('source_reference')->nullable();
                $table->date('effective_date')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('retention_ends_at')->nullable();
                $table->json('metadata')->nullable();
                $table->string('regulatory_source')->nullable();
                $table->string('regulatory_source_version')->nullable();
                $table->date('regulatory_effective_date')->nullable();
                $table->text('regulatory_applicability')->nullable();
                $table->timestamps();
                $table->index(['uas_operator_id', 'status'], 'evdoc_operator_status_idx');
                $table->index(['category', 'status'], 'evdoc_category_status_idx');
                $table->index('expires_at', 'evdoc_expires_idx');
            });
        }

        if (! Schema::hasTable('uas_evidence_links')) {
            Schema::create('uas_evidence_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_evidence_document_id')->constrained('uas_evidence_documents')->cascadeOnDelete();
                $table->string('evidenceable_type');
                $table->unsignedBigInteger('evidenceable_id');
                $table->string('evidence_role', 80)->default('supporting');
                $table->string('requirement_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('attached_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('attached_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['uas_evidence_document_id', 'evidenceable_type', 'evidenceable_id', 'evidence_role'], 'evlink_doc_target_role_uidx');
                $table->index(['evidenceable_type', 'evidenceable_id'], 'evlink_target_idx');
                $table->index(['requirement_id', 'evidence_role'], 'evlink_req_role_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_evidence_links');
        Schema::dropIfExists('uas_evidence_documents');
    }
};

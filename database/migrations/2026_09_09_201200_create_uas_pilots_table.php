<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uas_pilots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('preferred_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sacaa_certificate_number')->nullable()->unique();
            $table->string('rpc_category')->default('unknown');
            $table->json('ratings')->nullable();
            $table->string('medical_status')->default('unverified');
            $table->string('radiotelephony_qualification')->default('unverified');
            $table->string('language_proficiency')->nullable();
            $table->json('training_history')->nullable();
            $table->json('examiner_records')->nullable();
            $table->json('operator_affiliations')->nullable();
            $table->json('supporting_document_references')->nullable();
            $table->string('profile_status')->default('draft');
            $table->string('regulatory_source');
            $table->string('regulatory_source_version');
            $table->date('regulatory_effective_date');
            $table->string('regulatory_applicability');
            $table->string('responsible_role');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('profile_status');
            $table->index('rpc_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_pilots');
    }
};

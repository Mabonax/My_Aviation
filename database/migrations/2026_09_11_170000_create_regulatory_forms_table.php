<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regulatory_forms')) {
            return;
        }

        Schema::create('regulatory_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('previous_form_id')->nullable();
            $table->string('form_code', 80);
            $table->string('form_title');
            $table->string('regulatory_area', 120);
            $table->string('revision', 80);
            $table->date('effective_date');
            $table->date('superseded_date')->nullable();
            $table->string('source_reference');
            $table->string('source_url')->nullable();
            $table->string('required_transaction', 180);
            $table->string('status')->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['form_code', 'revision'], 'reg_forms_code_revision_unique');
            $table->index(['regulatory_area', 'status'], 'reg_forms_area_status_idx');
            $table->index('previous_form_id', 'reg_forms_previous_idx');
            $table->foreign('previous_form_id', 'reg_forms_previous_fk')->references('id')->on('regulatory_forms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_forms');
    }
};

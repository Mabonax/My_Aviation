<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regulatory_external_integrations')) {
            return;
        }

        Schema::create('regulatory_external_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('authority', 120);
            $table->string('classification', 40);
            $table->string('regulatory_area', 120);
            $table->string('supported_process');
            $table->string('authoritative_url')->nullable();
            $table->string('evidence_required');
            $table->text('workflow_notes');
            $table->boolean('api_assumption_blocked')->default(true);
            $table->string('status', 40)->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['authority', 'supported_process'], 'reg_ext_authority_process_unique');
            $table->index(['classification', 'status'], 'reg_ext_class_status_idx');
            $table->index(['regulatory_area', 'status'], 'reg_ext_area_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_external_integrations');
    }
};

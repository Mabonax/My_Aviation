<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operators')) {
            Schema::create('uas_operators', function (Blueprint $table) {
                $table->id();
                $table->string('legal_entity');
                $table->string('trading_name')->nullable();
                $table->string('registration_number')->nullable()->unique();
                $table->string('uasoc_number')->nullable()->unique();
                $table->date('certificate_issue_date')->nullable();
                $table->date('certificate_expiry_date')->nullable();
                $table->string('status')->default('draft');
                $table->string('accountable_manager');
                $table->string('responsible_person_flight_operations');
                $table->string('responsible_person_aircraft');
                $table->string('safety_manager')->nullable();
                $table->string('security_coordinator')->nullable();
                $table->json('operating_bases')->nullable();
                $table->json('approved_aircraft')->nullable();
                $table->json('approved_pilots')->nullable();
                $table->json('operations_specifications')->nullable();
                $table->json('evidence_references')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->string('responsible_role');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'certificate_expiry_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_operators');
    }
};
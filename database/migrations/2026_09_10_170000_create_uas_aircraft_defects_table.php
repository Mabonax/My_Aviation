<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_aircraft_defects')) {
            Schema::create('uas_aircraft_defects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->cascadeOnDelete();
                $table->foreignId('uas_mission_id')->nullable()->constrained('uas_missions')->nullOnDelete();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('defect_number')->unique();
                $table->string('source');
                $table->string('severity');
                $table->string('status')->default('open');
                $table->string('serviceability_impact')->default('none');
                $table->string('title');
                $table->text('description');
                $table->text('immediate_action')->nullable();
                $table->timestamp('reported_at');
                $table->json('evidence_references')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
                $table->index(['uas_aircraft_id', 'status']);
                $table->index(['uas_mission_id', 'source']);
                $table->index(['severity', 'serviceability_impact']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_aircraft_defects');
    }
};
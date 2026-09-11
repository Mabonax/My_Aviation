<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_mission_crew_members')) {
            Schema::create('uas_mission_crew_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_mission_id')->constrained('uas_missions')->cascadeOnDelete();
                $table->foreignId('uas_pilot_id')->nullable()->constrained('uas_pilots')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('crew_role');
                $table->string('display_name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('briefing_status')->default('pending');
                $table->string('competency_status')->default('not_checked');
                $table->string('acceptance_status')->default('pending');
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->text('notes')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date')->nullable();
                $table->text('regulatory_applicability')->nullable();
                $table->timestamps();
                $table->index(['uas_mission_id', 'crew_role']);
                $table->index(['briefing_status', 'acceptance_status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_mission_crew_members');
    }
};
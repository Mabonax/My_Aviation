<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uas_maintenance_authorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('evidence_reference');
            $table->date('valid_until');
            $table->boolean('can_return_to_service')->default(false);
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
            $table->index(['uas_operator_id', 'uas_aircraft_id', 'user_id'], 'mnt_authority_scope_idx');
        });
        Schema::table('uas_aircraft', function (Blueprint $table) {
            $table->foreignId('maintenance_restriction_task_id')->nullable()->constrained('uas_maintenance_tasks')->restrictOnDelete();
        });
        Schema::create('uas_maintenance_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->restrictOnDelete();
            $table->foreignId('maintenance_task_id')->unique()->constrained('uas_maintenance_tasks')->restrictOnDelete();
            $table->foreignId('restriction_task_id')->constrained('uas_maintenance_tasks')->restrictOnDelete();
            $table->foreignId('released_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('released_at');
            $table->json('evidence');
            $table->string('previous_operational_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_maintenance_releases');
        Schema::table('uas_aircraft', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maintenance_restriction_task_id');
        });
        Schema::dropIfExists('uas_maintenance_authorities');
    }
};

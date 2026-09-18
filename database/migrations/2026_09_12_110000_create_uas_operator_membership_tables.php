<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operator_memberships')) {
            Schema::create('uas_operator_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('membership_role', 80);
                $table->string('status', 40)->default('pending');
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('left_at')->nullable();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'uas_op_mem_user_status_idx');
                $table->index(['uas_operator_id', 'status'], 'uas_op_mem_op_status_idx');
            });
        }

        if (! Schema::hasTable('uas_operator_pilots')) {
            Schema::create('uas_operator_pilots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
                $table->foreignId('uas_pilot_id')->constrained('uas_pilots')->restrictOnDelete();
                $table->string('assignment_role', 80)->default('remote_pilot');
                $table->string('status', 40)->default('active');
                $table->date('approved_from')->nullable();
                $table->date('approved_until')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['uas_operator_id', 'uas_pilot_id'], 'uas_op_pilot_unique');
                $table->index(['uas_pilot_id', 'status'], 'uas_op_pilot_status_idx');
            });
        }

        if (! Schema::hasTable('uas_operator_aircraft')) {
            Schema::create('uas_operator_aircraft', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
                $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->restrictOnDelete();
                $table->string('assignment_role', 80)->default('operated_aircraft');
                $table->string('status', 40)->default('active');
                $table->date('approved_from')->nullable();
                $table->date('approved_until')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['uas_operator_id', 'uas_aircraft_id'], 'uas_op_aircraft_unique');
                $table->index(['uas_aircraft_id', 'status'], 'uas_op_aircraft_status_idx');
            });
        }

        if (! Schema::hasColumn('uas_missions', 'uas_operator_id')) {
            Schema::table('uas_missions', function (Blueprint $table) {
                $table->foreignId('uas_operator_id')
                    ->nullable()
                    ->after('operation_category')
                    ->constrained('uas_operators')
                    ->nullOnDelete();
                $table->index(['uas_operator_id', 'planned_start_at'], 'uas_missions_operator_plan_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('uas_missions', 'uas_operator_id')) {
            Schema::table('uas_missions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('uas_operator_id');
            });
        }

        Schema::dropIfExists('uas_operator_aircraft');
        Schema::dropIfExists('uas_operator_pilots');
        Schema::dropIfExists('uas_operator_memberships');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uas_missions') && ! Schema::hasColumn('uas_missions', 'actual_takeoff_at')) {
            Schema::table('uas_missions', function (Blueprint $table) {
                $table->timestamp('actual_takeoff_at')->nullable()->after('planned_end_at');
                $table->timestamp('actual_landing_at')->nullable()->after('actual_takeoff_at');
                $table->unsignedInteger('actual_flight_duration_minutes')->nullable()->after('actual_landing_at');
                $table->timestamp('completed_at')->nullable()->after('actual_flight_duration_minutes');
                $table->json('post_flight_declaration')->nullable()->after('post_flight_propagation_results');
                $table->index(['actual_landing_at', 'lifecycle_state'], 'mission_actual_landing_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('uas_missions') && Schema::hasColumn('uas_missions', 'actual_takeoff_at')) {
            Schema::table('uas_missions', function (Blueprint $table) {
                $table->dropIndex('mission_actual_landing_idx');
                $table->dropColumn([
                    'actual_takeoff_at',
                    'actual_landing_at',
                    'actual_flight_duration_minutes',
                    'completed_at',
                    'post_flight_declaration',
                ]);
            });
        }
    }
};


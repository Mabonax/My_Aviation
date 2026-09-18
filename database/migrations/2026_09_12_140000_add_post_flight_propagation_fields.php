<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pilot_log_entries') && ! Schema::hasColumn('pilot_log_entries', 'uas_mission_id')) {
            Schema::table('pilot_log_entries', function (Blueprint $table) {
                $table->foreignId('uas_mission_id')->nullable()->after('uas_pilot_id')->constrained('uas_missions')->nullOnDelete();
                $table->unique('uas_mission_id', 'pilot_log_mission_uidx');
            });
        }

        if (Schema::hasTable('aircraft_flight_folios') && ! Schema::hasColumn('aircraft_flight_folios', 'uas_mission_id')) {
            Schema::table('aircraft_flight_folios', function (Blueprint $table) {
                $table->foreignId('uas_mission_id')->nullable()->after('uas_pilot_id')->constrained('uas_missions')->nullOnDelete();
                $table->unique('uas_mission_id', 'folio_mission_uidx');
            });
        }

        if (Schema::hasTable('uas_missions') && ! Schema::hasColumn('uas_missions', 'post_flight_propagation_state')) {
            Schema::table('uas_missions', function (Blueprint $table) {
                $table->string('post_flight_propagation_state')->default('pending')->after('release_gate_results');
                $table->timestamp('post_flight_propagated_at')->nullable()->after('post_flight_propagation_state');
                $table->json('post_flight_propagation_results')->nullable()->after('post_flight_propagated_at');
                $table->index(['post_flight_propagation_state', 'planned_start_at'], 'mission_pf_state_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('uas_missions') && Schema::hasColumn('uas_missions', 'post_flight_propagation_state')) {
            Schema::table('uas_missions', function (Blueprint $table) {
                $table->dropIndex('mission_pf_state_idx');
                $table->dropColumn(['post_flight_propagation_state', 'post_flight_propagated_at', 'post_flight_propagation_results']);
            });
        }

        if (Schema::hasTable('aircraft_flight_folios') && Schema::hasColumn('aircraft_flight_folios', 'uas_mission_id')) {
            Schema::table('aircraft_flight_folios', function (Blueprint $table) {
                $table->dropUnique('folio_mission_uidx');
                $table->dropConstrainedForeignId('uas_mission_id');
            });
        }

        if (Schema::hasTable('pilot_log_entries') && Schema::hasColumn('pilot_log_entries', 'uas_mission_id')) {
            Schema::table('pilot_log_entries', function (Blueprint $table) {
                $table->dropUnique('pilot_log_mission_uidx');
                $table->dropConstrainedForeignId('uas_mission_id');
            });
        }
    }
};


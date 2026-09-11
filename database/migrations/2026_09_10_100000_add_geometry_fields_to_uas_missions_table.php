<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_missions', function (Blueprint $table) {
            if (! Schema::hasColumn('uas_missions', 'location_search_query')) {
                $table->string('location_search_query')->nullable()->after('location');
            }

            if (! Schema::hasColumn('uas_missions', 'takeoff_point')) {
                $table->json('takeoff_point')->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('uas_missions', 'landing_point')) {
                $table->json('landing_point')->nullable()->after('takeoff_point');
            }

            if (! Schema::hasColumn('uas_missions', 'flight_route')) {
                $table->json('flight_route')->nullable()->after('mission_polygon');
            }

            if (! Schema::hasColumn('uas_missions', 'flight_radius_m')) {
                $table->unsignedInteger('flight_radius_m')->nullable()->after('flight_route');
            }
        });
    }

    public function down(): void
    {
        Schema::table('uas_missions', function (Blueprint $table) {
            if (Schema::hasColumn('uas_missions', 'flight_radius_m')) {
                $table->dropColumn('flight_radius_m');
            }

            if (Schema::hasColumn('uas_missions', 'flight_route')) {
                $table->dropColumn('flight_route');
            }

            if (Schema::hasColumn('uas_missions', 'landing_point')) {
                $table->dropColumn('landing_point');
            }

            if (Schema::hasColumn('uas_missions', 'takeoff_point')) {
                $table->dropColumn('takeoff_point');
            }

            if (Schema::hasColumn('uas_missions', 'location_search_query')) {
                $table->dropColumn('location_search_query');
            }
        });
    }
};

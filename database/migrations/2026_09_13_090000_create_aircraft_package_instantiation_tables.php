<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uas_aircraft_models') && ! Schema::hasColumn('uas_aircraft_models', 'battery_package')) {
            Schema::table('uas_aircraft_models', function (Blueprint $table) {
                $table->json('battery_package')->nullable()->after('notes');
                $table->json('component_package')->nullable()->after('battery_package');
                $table->json('maintenance_package')->nullable()->after('component_package');
                $table->string('package_status', 40)->default('not_configured')->after('maintenance_package');
            });
        }

        if (Schema::hasTable('uas_aircraft') && ! Schema::hasColumn('uas_aircraft', 'package_instantiation_state')) {
            Schema::table('uas_aircraft', function (Blueprint $table) {
                $table->string('package_instantiation_state', 40)->default('not_applicable')->after('onboarding_status');
                $table->timestamp('package_instantiated_at')->nullable()->after('package_instantiation_state');
                $table->json('package_instantiation_results')->nullable()->after('package_instantiated_at');
                $table->index(['package_instantiation_state', 'aircraft_model_id'], 'air_pkg_state_model_idx');
            });
        }

        if (Schema::hasTable('uas_batteries') && ! Schema::hasColumn('uas_batteries', 'package_item_key')) {
            Schema::table('uas_batteries', function (Blueprint $table) {
                $table->foreignId('source_aircraft_model_id')->nullable()->after('compatible_uas_aircraft_id')->constrained('uas_aircraft_models')->nullOnDelete();
                $table->string('package_item_key')->nullable()->after('source_aircraft_model_id');
                $table->timestamp('package_instantiated_at')->nullable()->after('package_item_key');
                $table->unique(['compatible_uas_aircraft_id', 'package_item_key'], 'bat_air_pkg_item_uidx');
            });
        }

        if (! Schema::hasTable('uas_aircraft_components')) {
            Schema::create('uas_aircraft_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->cascadeOnDelete();
                $table->foreignId('source_aircraft_model_id')->nullable()->constrained('uas_aircraft_models')->nullOnDelete();
                $table->string('package_item_key');
                $table->string('component_uid')->unique();
                $table->string('component_type', 80);
                $table->string('name', 180);
                $table->string('manufacturer')->nullable();
                $table->string('model')->nullable();
                $table->string('serial_number')->nullable();
                $table->timestamp('installed_at')->nullable();
                $table->decimal('life_limit_hours', 10, 2)->nullable();
                $table->unsignedInteger('life_limit_cycles')->nullable();
                $table->decimal('accumulated_hours', 10, 2)->default(0);
                $table->unsignedInteger('accumulated_cycles')->default(0);
                $table->string('status', 40)->default('active');
                $table->json('maintenance_baseline')->nullable();
                $table->json('evidence_references')->nullable();
                $table->timestamps();
                $table->unique(['uas_aircraft_id', 'package_item_key'], 'comp_air_pkg_item_uidx');
                $table->index(['uas_aircraft_id', 'status'], 'comp_air_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_aircraft_components');

        if (Schema::hasTable('uas_batteries') && Schema::hasColumn('uas_batteries', 'package_item_key')) {
            Schema::table('uas_batteries', function (Blueprint $table) {
                $table->dropUnique('bat_air_pkg_item_uidx');
                $table->dropConstrainedForeignId('source_aircraft_model_id');
                $table->dropColumn(['package_item_key', 'package_instantiated_at']);
            });
        }

        if (Schema::hasTable('uas_aircraft') && Schema::hasColumn('uas_aircraft', 'package_instantiation_state')) {
            Schema::table('uas_aircraft', function (Blueprint $table) {
                $table->dropIndex('air_pkg_state_model_idx');
                $table->dropColumn(['package_instantiation_state', 'package_instantiated_at', 'package_instantiation_results']);
            });
        }

        if (Schema::hasTable('uas_aircraft_models') && Schema::hasColumn('uas_aircraft_models', 'battery_package')) {
            Schema::table('uas_aircraft_models', function (Blueprint $table) {
                $table->dropColumn(['battery_package', 'component_package', 'maintenance_package', 'package_status']);
            });
        }
    }
};


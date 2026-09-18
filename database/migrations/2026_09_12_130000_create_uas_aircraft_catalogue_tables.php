<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_manufacturers')) {
            Schema::create('uas_manufacturers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('website_url')->nullable();
                $table->string('status', 40)->default('active');
                $table->timestamps();

                $table->index('status', 'uas_mfr_status_idx');
            });
        }

        if (! Schema::hasTable('uas_aircraft_models')) {
            Schema::create('uas_aircraft_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manufacturer_id')->constrained('uas_manufacturers')->restrictOnDelete();
                $table->string('model');
                $table->string('family')->nullable();
                $table->string('aircraft_type', 80);
                $table->string('primary_use')->nullable();
                $table->string('status', 80)->nullable();
                $table->decimal('weight_kg', 8, 3)->nullable();
                $table->decimal('mtow_kg', 8, 3)->nullable();
                $table->decimal('max_payload_kg', 8, 3)->nullable();
                $table->unsignedInteger('max_flight_time_min')->nullable();
                $table->decimal('max_speed_m_s', 8, 3)->nullable();
                $table->decimal('max_range_km', 8, 3)->nullable();
                $table->unsignedInteger('service_ceiling_m')->nullable();
                $table->decimal('max_wind_m_s', 8, 3)->nullable();
                $table->string('ip_rating', 80)->nullable();
                $table->string('operating_temp_c', 80)->nullable();
                $table->text('dimensions')->nullable();
                $table->unsignedInteger('wingspan_mm')->nullable();
                $table->text('gnss')->nullable();
                $table->text('camera_payload_summary')->nullable();
                $table->string('remote_id')->nullable();
                $table->text('source_url')->nullable();
                $table->text('image_source_url')->nullable();
                $table->text('image_license_status')->nullable();
                $table->text('notes')->nullable();
                $table->date('verified_at')->nullable();
                $table->string('media_status')->nullable();
                $table->string('source_priority')->nullable();
                $table->string('catalogue_status', 40)->default('draft');
                $table->string('local_image_path')->nullable();
                $table->timestamp('media_approved_at')->nullable();
                $table->foreignId('media_approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['manufacturer_id', 'model'], 'uas_model_mfr_model_unique');
                $table->index(['aircraft_type', 'catalogue_status'], 'uas_model_type_status_idx');
                $table->index(['catalogue_status', 'verified_at'], 'uas_model_catalogue_verified_idx');
            });
        }

        if (! Schema::hasColumn('uas_aircraft', 'aircraft_model_id')) {
            Schema::table('uas_aircraft', function (Blueprint $table) {
                $table->foreignId('aircraft_model_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('uas_aircraft_models')
                    ->nullOnDelete();
                $table->string('onboarding_status', 40)->default('active')->after('operational_status');
                $table->string('internal_asset_number')->nullable()->after('serial_number');
                $table->string('supplier')->nullable()->after('operator');
                $table->string('firmware_version')->nullable()->after('supplier');
                $table->string('flight_controller_serial')->nullable()->after('firmware_version');
                $table->string('remote_id_serial')->nullable()->after('flight_controller_serial');
                $table->index(['aircraft_model_id', 'operational_status'], 'uas_aircraft_model_state_idx');
                $table->index('onboarding_status', 'uas_aircraft_onboarding_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('uas_aircraft', 'aircraft_model_id')) {
            Schema::table('uas_aircraft', function (Blueprint $table) {
                $table->dropIndex('uas_aircraft_model_state_idx');
                $table->dropIndex('uas_aircraft_onboarding_status_idx');
                $table->dropConstrainedForeignId('aircraft_model_id');
                $table->dropColumn([
                    'onboarding_status',
                    'internal_asset_number',
                    'supplier',
                    'firmware_version',
                    'flight_controller_serial',
                    'remote_id_serial',
                ]);
            });
        }

        Schema::dropIfExists('uas_aircraft_models');
        Schema::dropIfExists('uas_manufacturers');
    }
};

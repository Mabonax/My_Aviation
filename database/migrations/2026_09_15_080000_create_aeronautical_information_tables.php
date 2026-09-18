<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uas_aeronautical_dataset_state', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('revision')->default(0);
        });
        DB::table('uas_aeronautical_dataset_state')->insert(['id' => 1, 'revision' => 0]);

        Schema::create('uas_aeronautical_provider_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 80)->index('aim_sync_provider');
            $table->string('status', 20);
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('dataset_timestamp')->nullable();
            $table->boolean('usable_for_release')->default(false);
            $table->string('source_classification', 40);
            $table->json('coverage')->nullable();
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->text('error')->nullable();
        });
        Schema::create('uas_aeronautical_source_records', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 80);
            $table->string('source_classification', 40);
            $table->string('source_identifier', 160);
            $table->string('source_revision', 160);
            $table->text('source_url')->nullable();
            $table->longText('raw_message')->nullable();
            $table->json('raw_payload');
            $table->char('checksum', 64);
            $table->char('identity_hash', 64)->unique('aim_source_identity');
            $table->dateTime('received_at');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();
        });
        Schema::create('uas_aeronautical_information_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('aim_item_uuid');
            $table->foreignId('source_record_id')->constrained('uas_aeronautical_source_records', indexName: 'aim_item_source_fk')->restrictOnDelete();
            $table->string('information_type', 30)->index('aim_item_type');
            $table->string('provider', 80);
            $table->string('source_identifier', 160);
            $table->string('source_revision', 160);
            $table->string('source_classification', 40);
            $table->boolean('usable_for_release')->default(false);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('normalized_text')->nullable();
            $table->string('status', 20)->default('active');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();
            $table->boolean('permanent')->default(false);
            foreach (['lower', 'upper'] as $limit) {
                $table->decimal($limit.'_limit_value', 10, 2)->nullable();
                $table->string($limit.'_limit_unit', 10)->nullable();
                $table->string($limit.'_limit_reference', 10)->nullable();
            }
            $table->string('geometry_type', 30)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->decimal('radius_nm', 10, 3)->nullable();
            $table->json('geometry_json')->nullable();
            $table->string('fir_code', 8)->nullable();
            $table->string('aerodrome_code', 8)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->json('interpretation');
            $table->char('checksum', 64);
            $table->dateTime('received_at');
            $table->dateTime('superseded_at')->nullable();
            $table->unsignedBigInteger('supersedes_id')->nullable();
            $table->char('identity_hash', 64)->unique('aim_item_identity');
            $table->timestamps();
            $table->index(['provider', 'source_identifier'], 'aim_item_source');
            $table->index(['superseded_at', 'status', 'effective_until'], 'aim_item_current');
        });
        Schema::create('uas_mission_briefings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('uas_missions', indexName: 'aim_brief_mission_fk')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->dateTime('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users', indexName: 'aim_brief_user_fk')->restrictOnDelete();
            $table->dateTime('valid_until');
            $table->dateTime('source_dataset_timestamp')->nullable();
            $table->char('source_dataset_hash', 64);
            $table->char('mission_hash', 64);
            $table->char('policy_hash', 64);
            $table->string('assessment_version', 40);
            $table->string('overall_status', 10);
            $table->unsignedInteger('blockers_count');
            $table->unsignedInteger('warnings_count');
            $table->boolean('acknowledgement_required');
            $table->json('snapshot');
            $table->unique(['mission_id', 'revision'], 'aim_brief_revision');
        });
        Schema::create('uas_mission_briefing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('briefing_id')->constrained('uas_mission_briefings', indexName: 'aim_bitem_brief_fk')->restrictOnDelete();
            $table->foreignId('aeronautical_information_item_id')->constrained('uas_aeronautical_information_items', indexName: 'aim_bitem_item_fk')->restrictOnDelete();
            $table->json('snapshot');
            $table->unique(['briefing_id', 'aeronautical_information_item_id'], 'aim_bitem_unique');
        });
        Schema::create('uas_mission_briefing_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('briefing_id')->constrained('uas_mission_briefings', indexName: 'aim_ack_brief_fk')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users', indexName: 'aim_ack_user_fk')->restrictOnDelete();
            $table->dateTime('acknowledged_at');
            $table->unique(['briefing_id', 'user_id'], 'aim_ack_unique');
        });
        Schema::table('uas_missions', function (Blueprint $table) {
            $table->json('aeronautical_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('uas_missions', fn (Blueprint $table) => $table->dropColumn('aeronautical_context'));
        foreach (['uas_mission_briefing_acknowledgements', 'uas_mission_briefing_items', 'uas_mission_briefings', 'uas_aeronautical_information_items', 'uas_aeronautical_source_records', 'uas_aeronautical_provider_syncs', 'uas_aeronautical_dataset_state'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

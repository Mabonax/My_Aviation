<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_aeronautical_provider_syncs', function (Blueprint $table) {
            $table->string('dataset_mode', 24)->default('unknown');
            $table->char('dataset_fingerprint', 64)->nullable();
            $table->char('approval_fingerprint', 64)->nullable();
            $table->text('sync_metadata')->nullable();
            $table->string('error_code', 40)->nullable();
            $table->unsignedBigInteger('duplicate_of_id')->nullable();
            $table->index(['provider', 'status', 'id'], 'aim_sync_outcome');
        });
    }

    public function down(): void
    {
        Schema::table('uas_aeronautical_provider_syncs', function (Blueprint $table) {
            $table->dropIndex('aim_sync_outcome');
            $table->dropColumn(['dataset_mode', 'dataset_fingerprint', 'approval_fingerprint', 'sync_metadata', 'error_code', 'duplicate_of_id']);
        });
    }
};

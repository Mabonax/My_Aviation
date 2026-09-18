<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_aeronautical_provider_syncs', function (Blueprint $table) {
            $table->unsignedBigInteger('completion_revision')->nullable()->index('aim_sync_completion');
        });
    }

    public function down(): void
    {
        Schema::table('uas_aeronautical_provider_syncs', function (Blueprint $table) {
            $table->dropIndex('aim_sync_completion');
            $table->dropColumn('completion_revision');
        });
    }
};

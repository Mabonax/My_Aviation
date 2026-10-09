<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_aircraft_components', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('removal_evidence')->nullable();
            $table->json('installation_evidence')->nullable();
            $table->foreignId('replaces_component_id')->nullable()->constrained('uas_aircraft_components')->restrictOnDelete();
            $table->unique('replaces_component_id', 'component_replacement_unique');
        });
    }
    public function down(): void
    {
        Schema::table('uas_aircraft_components', function (Blueprint $table) {
            $table->dropUnique('component_replacement_unique');
            $table->dropConstrainedForeignId('replaces_component_id');
            $table->dropConstrainedForeignId('removed_by');
            $table->dropColumn(['removed_at', 'removal_evidence', 'installation_evidence']);
        });
    }
};

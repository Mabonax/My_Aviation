<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_flight_tracks')) {
            Schema::create('uas_flight_tracks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('uas_mission_id')->constrained('uas_missions')->cascadeOnDelete();
                $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('source_type');
                $table->string('track_reference')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->json('points');
                $table->unsignedInteger('point_count')->default(0);
                $table->decimal('total_distance_km', 10, 3)->nullable();
                $table->unsignedInteger('max_altitude_ft')->nullable();
                $table->json('anomalies')->nullable();
                $table->text('notes')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date')->nullable();
                $table->text('regulatory_applicability')->nullable();
                $table->timestamps();
                $table->index(['uas_mission_id', 'started_at']);
                $table->index(['source_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_flight_tracks');
    }
};
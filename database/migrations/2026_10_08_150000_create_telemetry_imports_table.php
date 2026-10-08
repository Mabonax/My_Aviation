<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_operator_id')->constrained('uas_operators')->restrictOnDelete();
            $table->foreignId('uas_mission_id')->constrained('uas_missions')->restrictOnDelete();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->restrictOnDelete();
            $table->foreignId('uas_pilot_id')->constrained('uas_pilots')->restrictOnDelete();
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('format', 32);
            $table->string('content_sha256', 64);
            $table->string('flight_sha256', 64);
            $table->string('state', 32)->default('pending_review');
            $table->longText('raw_csv');
            $table->json('normalised_flight');
            $table->foreignId('uas_flight_track_id')->nullable()->constrained('uas_flight_tracks')->restrictOnDelete();
            $table->json('propagation_results')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['uas_operator_id', 'flight_sha256'], 'telemetry_operator_flight_unique');
            $table->index(['uas_mission_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_imports');
    }
};

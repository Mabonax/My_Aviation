<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workos_mobile_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_access_token_id')->unique()->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->text('sealed_session');
            $table->string('session_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workos_mobile_sessions');
    }
};

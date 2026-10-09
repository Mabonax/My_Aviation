<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_maintenance_tasks', function (Blueprint $table) {
            $table->unsignedInteger('interval_days')->nullable();
            $table->decimal('interval_hours', 10, 2)->nullable();
            $table->unsignedInteger('interval_cycles')->nullable();
            $table->foreignId('previous_task_id')->nullable()
                ->constrained('uas_maintenance_tasks')->restrictOnDelete();
            $table->unique('previous_task_id', 'maintenance_successor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('uas_maintenance_tasks', function (Blueprint $table) {
            $table->dropUnique('maintenance_successor_unique');
            $table->dropConstrainedForeignId('previous_task_id');
            $table->dropColumn(['interval_days', 'interval_hours', 'interval_cycles']);
        });
    }
};

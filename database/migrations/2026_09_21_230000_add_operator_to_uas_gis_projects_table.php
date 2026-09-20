<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('uas_gis_projects', 'uas_operator_id')) {
            Schema::table('uas_gis_projects', function (Blueprint $table) {
                $table->foreignId('uas_operator_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('uas_operators')
                    ->nullOnDelete();
                $table->index(['uas_operator_id', 'lifecycle_state'], 'uas_gis_operator_state_idx');
            });
        }

        DB::table('uas_gis_projects')
            ->whereNull('uas_operator_id')
            ->orderBy('id')
            ->eachById(function ($project): void {
                $operatorId = DB::table('uas_gis_project_missions as pgm')
                    ->join('uas_missions as m', 'm.id', '=', 'pgm.uas_mission_id')
                    ->where('pgm.uas_gis_project_id', $project->id)
                    ->whereNotNull('m.uas_operator_id')
                    ->value('m.uas_operator_id');

                if ($operatorId) {
                    DB::table('uas_gis_projects')->where('id', $project->id)->update(['uas_operator_id' => $operatorId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('uas_gis_projects', 'uas_operator_id')) {
            Schema::table('uas_gis_projects', function (Blueprint $table) {
                $table->dropForeign(['uas_operator_id']);
                $table->dropIndex('uas_gis_operator_state_idx');
                $table->dropColumn('uas_operator_id');
            });
        }
    }
};

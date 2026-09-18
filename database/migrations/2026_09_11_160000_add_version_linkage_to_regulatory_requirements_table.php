<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('regulatory_requirements')) {
            return;
        }

        Schema::table('regulatory_requirements', function (Blueprint $table) {
            if (! Schema::hasColumn('regulatory_requirements', 'previous_requirement_id')) {
                $table->unsignedBigInteger('previous_requirement_id')->nullable()->after('id');
            }
        });

        Schema::table('regulatory_requirements', function (Blueprint $table) {
            if (! Schema::hasIndex('regulatory_requirements', 'reg_requirement_previous_idx')) {
                $table->index('previous_requirement_id', 'reg_requirement_previous_idx');
            }

            $table->foreign('previous_requirement_id', 'reg_requirement_previous_fk')->references('id')->on('regulatory_requirements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('regulatory_requirements') || ! Schema::hasColumn('regulatory_requirements', 'previous_requirement_id')) {
            return;
        }

        Schema::table('regulatory_requirements', function (Blueprint $table) {
            $table->dropForeign('reg_requirement_previous_fk');
            $table->dropIndex('reg_requirement_previous_idx');
            $table->dropColumn('previous_requirement_id');
        });
    }
};

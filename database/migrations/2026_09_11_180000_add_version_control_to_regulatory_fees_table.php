<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('regulatory_fees')) {
            return;
        }

        Schema::table('regulatory_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('regulatory_fees', 'previous_fee_id')) {
                $table->unsignedBigInteger('previous_fee_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('regulatory_fees', 'status')) {
                $table->string('status')->default('active')->after('source_version');
            }
        });

        Schema::table('regulatory_fees', function (Blueprint $table) {
            if (! Schema::hasIndex('regulatory_fees', 'reg_fees_previous_idx')) {
                $table->index('previous_fee_id', 'reg_fees_previous_idx');
            }

            if (! Schema::hasIndex('regulatory_fees', 'reg_fees_tx_source_unique')) {
                $table->unique(['transaction_code', 'source_version'], 'reg_fees_tx_source_unique');
            }

            $table->foreign('previous_fee_id', 'reg_fees_previous_fk')->references('id')->on('regulatory_fees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('regulatory_fees')) {
            return;
        }

        Schema::table('regulatory_fees', function (Blueprint $table) {
            $table->dropForeign('reg_fees_previous_fk');
            $table->dropUnique('reg_fees_tx_source_unique');
            $table->dropIndex('reg_fees_previous_idx');
        });

        Schema::table('regulatory_fees', function (Blueprint $table) {
            if (Schema::hasColumn('regulatory_fees', 'previous_fee_id')) {
                $table->dropColumn('previous_fee_id');
            }

            if (Schema::hasColumn('regulatory_fees', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

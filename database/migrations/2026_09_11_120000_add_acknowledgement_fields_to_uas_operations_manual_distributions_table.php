<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_operations_manual_distributions')) {
            return;
        }

        Schema::table('uas_operations_manual_distributions', function (Blueprint $table) {
            if (! Schema::hasColumn('uas_operations_manual_distributions', 'acknowledgement_status')) {
                $table->string('acknowledgement_status')->default('pending')->after('distribution_status');
            }

            if (! Schema::hasColumn('uas_operations_manual_distributions', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('distributed_at');
            }

            if (! Schema::hasColumn('uas_operations_manual_distributions', 'acknowledged_by')) {
                $table->foreignId('acknowledged_by')->nullable()->after('acknowledged_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('uas_operations_manual_distributions', 'acknowledgement_statement')) {
                $table->string('acknowledgement_statement')->nullable()->after('acknowledged_by');
            }

            if (! Schema::hasColumn('uas_operations_manual_distributions', 'acknowledgement_notes')) {
                $table->text('acknowledgement_notes')->nullable()->after('acknowledgement_statement');
            }
        });

        Schema::table('uas_operations_manual_distributions', function (Blueprint $table) {
            if (! Schema::hasIndex('uas_operations_manual_distributions', 'uas_manual_ack_status_idx')) {
                $table->index(['manual_revision_id', 'acknowledgement_status'], 'uas_manual_ack_status_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('uas_operations_manual_distributions')) {
            return;
        }

        Schema::table('uas_operations_manual_distributions', function (Blueprint $table) {
            if (Schema::hasIndex('uas_operations_manual_distributions', 'uas_manual_ack_status_idx')) {
                $table->dropIndex('uas_manual_ack_status_idx');
            }

            if (Schema::hasColumn('uas_operations_manual_distributions', 'acknowledged_by')) {
                $table->dropConstrainedForeignId('acknowledged_by');
            }

            foreach (['acknowledgement_status', 'acknowledged_at', 'acknowledgement_statement', 'acknowledgement_notes'] as $column) {
                if (Schema::hasColumn('uas_operations_manual_distributions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

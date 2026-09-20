<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('uas_audit_entries', function (Blueprint $table) {
            $table->foreignId('uas_operator_id')->nullable()->after('user_id')->constrained('uas_operators')->nullOnDelete();
            $table->string('operator_context_source', 32)->nullable()->after('uas_operator_id');
            $table->index(['uas_operator_id', 'occurred_at'], 'uas_audit_operator_occurred_idx');
        });
    }
    public function down(): void {
        Schema::table('uas_audit_entries', function (Blueprint $table) {
            $table->dropIndex('uas_audit_operator_occurred_idx');
            $table->dropConstrainedForeignId('uas_operator_id');
            $table->dropColumn('operator_context_source');
        });
    }
};

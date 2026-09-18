<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_operator_memberships', function (Blueprint $table) {
            $table->string('source', 30)->default('admin')->after('status');
            $table->text('message')->nullable()->after('source');
            $table->timestamp('responded_at')->nullable()->after('activated_at');
            $table->foreignId('responded_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->index(['uas_operator_id', 'source', 'status'], 'uas_op_mem_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('uas_operator_memberships', function (Blueprint $table) {
            $table->dropIndex('uas_op_mem_source_status_idx');
            $table->dropConstrainedForeignId('responded_by');
            $table->dropColumn(['source', 'message', 'responded_at']);
        });
    }
};

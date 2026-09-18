<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compliance_notifications')) {
            return;
        }

        Schema::table('compliance_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('compliance_notifications', 'priority')) {
                $table->string('priority', 40)->default('normal')->after('channel');
            }

            if (! Schema::hasColumn('compliance_notifications', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->after('notification_type');
            }

            if (! Schema::hasColumn('compliance_notifications', 'delivery_attempts')) {
                $table->unsignedSmallInteger('delivery_attempts')->default(0)->after('status');
            }

            if (! Schema::hasColumn('compliance_notifications', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('message');
            }

            if (! Schema::hasColumn('compliance_notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('sent_at');
            }

            if (! Schema::hasColumn('compliance_notifications', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('read_at');
            }
        });

        Schema::table('compliance_notifications', function (Blueprint $table) {
            if (! Schema::hasIndex('compliance_notifications', 'cmp_notif_idempotency_unique')) {
                $table->unique('idempotency_key', 'cmp_notif_idempotency_unique');
            }

            if (! Schema::hasIndex('compliance_notifications', 'cmp_notif_status_channel_idx')) {
                $table->index(['status', 'channel', 'due_at'], 'cmp_notif_status_channel_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('compliance_notifications')) {
            return;
        }

        Schema::table('compliance_notifications', function (Blueprint $table) {
            $table->dropUnique('cmp_notif_idempotency_unique');
            $table->dropIndex('cmp_notif_status_channel_idx');
        });

        Schema::table('compliance_notifications', function (Blueprint $table) {
            foreach (['acknowledged_at', 'read_at', 'failure_reason', 'delivery_attempts', 'idempotency_key', 'priority'] as $column) {
                if (Schema::hasColumn('compliance_notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

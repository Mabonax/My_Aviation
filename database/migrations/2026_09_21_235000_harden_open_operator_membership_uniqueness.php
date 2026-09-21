<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uas_operator_memberships', function (Blueprint $table) {
            $table->string('open_membership_key', 255)->nullable()->after('status');
            $table->unique('open_membership_key', 'uas_op_mem_open_unique');
        });

        DB::table('uas_operator_memberships')
            ->whereIn('status', ['pending', 'active', 'suspended'])
            ->orderBy('id')
            ->get()
            ->each(function ($membership): void {
                $key = $membership->uas_operator_id.':'.$membership->user_id;
                $duplicate = DB::table('uas_operator_memberships')
                    ->where('open_membership_key', $key)
                    ->exists();

                if (! $duplicate) {
                    DB::table('uas_operator_memberships')->where('id', $membership->id)->update(['open_membership_key' => $key]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('uas_operator_memberships', function (Blueprint $table) {
            $table->dropUnique('uas_op_mem_open_unique');
            $table->dropColumn('open_membership_key');
        });
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('uas_operator_pilots', function(Blueprint $table){
  $table->foreignId('uas_operator_membership_id')->nullable()->after('uas_pilot_id')->constrained('uas_operator_memberships')->nullOnDelete();
  $table->timestamp('approved_at')->nullable()->after('approved_until');
  $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
  $table->timestamp('suspended_at')->nullable()->after('approved_by');
  $table->timestamp('ended_at')->nullable()->after('suspended_at');
  $table->index(['uas_operator_id','status','approved_from','approved_until'],'uas_op_pilot_validity_idx');
 }); }
 public function down(): void { Schema::table('uas_operator_pilots', function(Blueprint $table){
  $table->dropIndex('uas_op_pilot_validity_idx'); $table->dropConstrainedForeignId('uas_operator_membership_id'); $table->dropConstrainedForeignId('approved_by'); $table->dropColumn(['approved_at','suspended_at','ended_at']);
 }); }
};
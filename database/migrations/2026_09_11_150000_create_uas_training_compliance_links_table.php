<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_training_compliance_links')) {
            Schema::create('uas_training_compliance_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('training_course_id');
                $table->unsignedBigInteger('training_competency_id')->nullable();
                $table->unsignedBigInteger('training_competency_record_id')->nullable();
                $table->unsignedBigInteger('regulatory_requirement_id')->nullable();
                $table->string('source_type')->default('organisational_requirement');
                $table->string('requirement_reference');
                $table->string('title');
                $table->string('responsible_role');
                $table->text('applicability');
                $table->text('evidence_required');
                $table->string('validity_period')->nullable();
                $table->string('retention_period')->nullable();
                $table->date('due_date')->nullable();
                $table->string('link_status')->default('required');
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
                $table->index(['training_course_id', 'link_status'], 'uas_training_link_status_idx');
                $table->index(['source_type', 'requirement_reference'], 'uas_training_link_source_idx');
                $table->index('due_date', 'uas_training_link_due_idx');
                $table->foreign('training_course_id', 'uas_training_link_course_fk')->references('id')->on('uas_training_courses')->cascadeOnDelete();
                $table->foreign('training_competency_id', 'uas_training_link_competency_fk')->references('id')->on('uas_training_competencies')->nullOnDelete();
                $table->foreign('training_competency_record_id', 'uas_training_link_record_fk')->references('id')->on('uas_training_competency_records')->nullOnDelete();
                $table->foreign('regulatory_requirement_id', 'uas_training_link_requirement_fk')->references('id')->on('regulatory_requirements')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_training_compliance_links');
    }
};

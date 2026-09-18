<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_training_courses')) {
            Schema::create('uas_training_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('code')->unique();
                $table->string('title');
                $table->string('classification');
                $table->string('status')->default('draft');
                $table->text('summary')->nullable();
                $table->string('authority_approval_reference')->nullable();
                $table->string('regulatory_source');
                $table->string('regulatory_source_version');
                $table->date('regulatory_effective_date');
                $table->string('regulatory_applicability');
                $table->timestamps();
                $table->index(['classification', 'status'], 'uas_training_course_class_status_idx');
            });
        }

        if (! Schema::hasTable('uas_training_modules')) {
            Schema::create('uas_training_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_course_id')->constrained('uas_training_courses')->cascadeOnDelete();
                $table->unsignedInteger('sequence')->default(1);
                $table->string('title');
                $table->text('summary')->nullable();
                $table->timestamps();
                $table->index(['training_course_id', 'sequence'], 'uas_training_module_sequence_idx');
            });
        }

        if (! Schema::hasTable('uas_training_lessons')) {
            Schema::create('uas_training_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_module_id')->constrained('uas_training_modules')->cascadeOnDelete();
                $table->unsignedInteger('sequence')->default(1);
                $table->string('title');
                $table->string('lesson_type')->default('lesson');
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->timestamps();
                $table->index(['training_module_id', 'sequence'], 'uas_training_lesson_sequence_idx');
            });
        }

        if (! Schema::hasTable('uas_training_resources')) {
            Schema::create('uas_training_resources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_lesson_id')->constrained('uas_training_lessons')->cascadeOnDelete();
                $table->string('title');
                $table->string('resource_type')->default('document');
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('uas_training_assessments')) {
            Schema::create('uas_training_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_course_id')->constrained('uas_training_courses')->cascadeOnDelete();
                $table->string('title');
                $table->string('assessment_type')->default('quiz');
                $table->unsignedInteger('pass_mark')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('uas_training_competencies')) {
            Schema::create('uas_training_competencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_course_id')->constrained('uas_training_courses')->cascadeOnDelete();
                $table->string('title');
                $table->string('standard')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('uas_training_competency_records')) {
            Schema::create('uas_training_competency_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_course_id')->constrained('uas_training_courses')->cascadeOnDelete();
                $table->string('participant_name');
                $table->string('competency_title');
                $table->string('record_status')->default('planned');
                $table->date('completed_at')->nullable();
                $table->json('evidence_references')->nullable();
                $table->timestamps();
                $table->index(['training_course_id', 'record_status'], 'uas_training_record_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uas_training_competency_records');
        Schema::dropIfExists('uas_training_competencies');
        Schema::dropIfExists('uas_training_assessments');
        Schema::dropIfExists('uas_training_resources');
        Schema::dropIfExists('uas_training_lessons');
        Schema::dropIfExists('uas_training_modules');
        Schema::dropIfExists('uas_training_courses');
    }
};

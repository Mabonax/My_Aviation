<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uas_roles')) {
            Schema::create('uas_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->json('permissions');
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('uas_role_user')) {
            Schema::create('uas_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uas_role_id')->constrained('uas_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'uas_role_id']);
            });
        }

        if (! Schema::hasTable('pilot_certificates')) {
            Schema::create('pilot_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_pilot_id')->constrained('uas_pilots')->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('last_revalidation_date')->nullable();
            $table->date('post_revalidation_submission_due_at')->nullable();
            $table->string('status')->default('unknown');
            $table->string('regulatory_source');
            $table->string('regulatory_source_version');
            $table->date('regulatory_effective_date');
            $table->json('evidence_references')->nullable();
            $table->timestamps();
            $table->index(['uas_pilot_id', 'expiry_date']);
            });
        }

        if (! Schema::hasTable('pilot_log_entries')) {
            Schema::create('pilot_log_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_pilot_id')->constrained('uas_pilots')->cascadeOnDelete();
            $table->date('flight_date');
            $table->string('aircraft_registration')->nullable();
            $table->string('operation_type')->nullable();
            $table->decimal('flight_hours', 8, 2)->default(0);
            $table->string('launch_location')->nullable();
            $table->string('landing_location')->nullable();
            $table->text('remarks')->nullable();
            $table->json('evidence_references')->nullable();
            $table->timestamps();
            $table->index(['uas_pilot_id', 'flight_date']);
            });
        }

        if (! Schema::hasTable('uas_aircraft')) {
            Schema::create('uas_aircraft', function (Blueprint $table) {
            $table->id();
            $table->string('registration')->unique();
            $table->string('manufacturer');
            $table->string('model');
            $table->string('serial_number')->unique();
            $table->string('aircraft_category')->default('uas');
            $table->string('owner')->nullable();
            $table->string('operator')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->string('operational_status')->default('pending_registration');
            $table->string('base_location')->nullable();
            $table->json('manual_references')->nullable();
            $table->json('evidence_references')->nullable();
            $table->timestamps();
            $table->index('operational_status');
            });
        }

        if (! Schema::hasTable('aircraft_registrations')) {
            Schema::create('aircraft_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->cascadeOnDelete();
            $table->string('registration_number');
            $table->string('lifecycle_state')->default('initial');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->json('evidence_references')->nullable();
            $table->timestamps();
            $table->index(['uas_aircraft_id', 'lifecycle_state']);
            });
        }

        if (! Schema::hasTable('aircraft_approvals')) {
            Schema::create('aircraft_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->cascadeOnDelete();
            $table->string('approval_type')->default('uasla');
            $table->string('approval_number')->unique();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('scope')->nullable();
            $table->text('restrictions')->nullable();
            $table->string('status')->default('unknown');
            $table->json('evidence_references')->nullable();
            $table->timestamps();
            $table->index(['uas_aircraft_id', 'expiry_date']);
            });
        }

        if (! Schema::hasTable('aircraft_flight_folios')) {
            Schema::create('aircraft_flight_folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uas_aircraft_id')->constrained('uas_aircraft')->cascadeOnDelete();
            $table->foreignId('uas_pilot_id')->nullable()->constrained('uas_pilots')->nullOnDelete();
            $table->date('flight_date');
            $table->string('folio_reference')->unique();
            $table->decimal('flight_hours', 8, 2)->default(0);
            $table->integer('battery_cycles')->nullable();
            $table->json('charging_fuel_oil_records')->nullable();
            $table->json('maintenance_certification_entries')->nullable();
            $table->text('defects_reported')->nullable();
            $table->boolean('available_offline')->default(false);
            $table->json('evidence_references')->nullable();
            $table->timestamps();
            $table->index(['uas_aircraft_id', 'flight_date']);
            });
        }

        if (! Schema::hasTable('regulatory_documents')) {
            Schema::create('regulatory_documents', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('documentable');
            $table->string('category');
            $table->string('title');
            $table->string('document_reference')->nullable();
            $table->string('status')->default('draft');
            $table->string('regulatory_source')->nullable();
            $table->string('source_version')->nullable();
            $table->date('effective_date')->nullable();
            $table->timestamp('retention_starts_at')->nullable();
            $table->timestamp('retention_ends_at')->nullable();
            $table->boolean('locked')->default(false);
            $table->boolean('archived')->default(false);
            $table->timestamps();
            $table->index(['category', 'status']);
            });
        }

        if (! Schema::hasTable('regulatory_requirements')) {
            Schema::create('regulatory_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('requirement_id')->unique();
            $table->string('regulation_part');
            $table->string('clause_reference')->nullable();
            $table->string('title');
            $table->text('requirement_text');
            $table->string('responsible_party');
            $table->text('applicability');
            $table->text('system_control');
            $table->text('evidence_required')->nullable();
            $table->string('frequency')->nullable();
            $table->string('validity_period')->nullable();
            $table->string('retention_period')->nullable();
            $table->date('effective_date');
            $table->date('superseded_date')->nullable();
            $table->string('official_source');
            $table->string('source_version');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['regulation_part', 'status']);
            });
        }

        if (! Schema::hasTable('regulatory_fees')) {
            Schema::create('regulatory_fees', function (Blueprint $table) {
            $table->id();
            $table->string('regulation_part');
            $table->string('transaction_code');
            $table->string('description');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('ZAR');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source');
            $table->string('source_version');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['transaction_code', 'effective_from']);
            });
        }

        if (! Schema::hasTable('compliance_findings')) {
            Schema::create('compliance_findings', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('compliable');
            $table->string('requirement_id');
            $table->string('state')->default('unknown');
            $table->string('severity')->default('info');
            $table->text('summary');
            $table->text('recommended_action')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['requirement_id', 'state']);
            $table->index(['severity', 'resolved_at']);
            });
        }

        if (! Schema::hasTable('compliance_notifications')) {
            Schema::create('compliance_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('notifiable_record', 'cmp_notif_record_idx');
            $table->string('requirement_id')->nullable();
            $table->string('notification_type');
            $table->string('channel')->default('in_application');
            $table->string('status')->default('pending');
            $table->string('subject');
            $table->text('message');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_at']);
            });
        }

        if (! Schema::hasTable('record_retention_rules')) {
            Schema::create('record_retention_rules', function (Blueprint $table) {
            $table->id();
            $table->string('record_category')->unique();
            $table->string('regulatory_source');
            $table->string('source_version');
            $table->date('effective_date');
            $table->string('retention_period');
            $table->text('applicability');
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('record_retention_rules');
        Schema::dropIfExists('compliance_notifications');
        Schema::dropIfExists('compliance_findings');
        Schema::dropIfExists('regulatory_fees');
        Schema::dropIfExists('regulatory_requirements');
        Schema::dropIfExists('regulatory_documents');
        Schema::dropIfExists('aircraft_flight_folios');
        Schema::dropIfExists('aircraft_approvals');
        Schema::dropIfExists('aircraft_registrations');
        Schema::dropIfExists('uas_aircraft');
        Schema::dropIfExists('pilot_log_entries');
        Schema::dropIfExists('pilot_certificates');
        Schema::dropIfExists('uas_role_user');
        Schema::dropIfExists('uas_roles');
    }
};

<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Compliance\Application\Queries\Phase1VerificationReport;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Documents\Domain\Models\RegulatoryDocument;
use App\Domains\Uas\FlightFolios\Domain\Models\AircraftFlightFolio;
use App\Domains\Uas\FlightLogs\Domain\Models\PilotLogEntry;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\RecordRetentionRule;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;

it('verifies the authenticated Phase 1 evidence workflow and export', function () {
    $this->withoutVite();

    $user = seedPhase1VerificationDataset();

    $this->get('/phase-1/verification')->assertRedirect('/login');

    $response = $this->actingAs($user)->get('/phase-1/verification');

    $response->assertOk();

    $report = app(Phase1VerificationReport::class)->execute();

    expect($report['status'])->toBe('VERIFICATION_READY')
        ->and($report['counts']['roles'])->toBe(1)
        ->and($report['counts']['pilots'])->toBe(1)
        ->and($report['counts']['aircraft'])->toBe(1)
        ->and(collect($report['requirements'])->where('status', 'verified_ready')->count())->toBe(13)
        ->and(collect($report['requirements'])->where('status', 'needs_planning_run')->count())->toBe(1);

    $export = $this->actingAs($user)->get('/phase-1/verification/export');

    $export->assertOk();
    expect($export->streamedContent())
        ->toContain('Requirement,Name,Status,Evidence')
        ->toContain('FR-PIL-001')
        ->toContain('FR-REC-003');
});

it('plans due certificate expiry notifications and records an audit event', function () {
    $user = seedPhase1VerificationDataset();

    $this->actingAs($user)
        ->artisan('uas:plan-expiry-notifications')
        ->expectsOutput('Planned 1 UAS compliance notifications.')
        ->assertSuccessful();

    $this->artisan('uas:plan-expiry-notifications')
        ->expectsOutput('Planned 0 UAS compliance notifications.')
        ->assertSuccessful();

    $notification = ComplianceNotification::query()->firstOrFail();

    expect($notification->notification_type)->toBe('rpc_expiry_alert_30_days')
        ->and($notification->status)->toBe('pending')
        ->and($notification->user_id)->toBe($user->id);

    $audit = UasAuditEntry::query()
        ->where('action', 'compliance.notification.planned')
        ->firstOrFail();

    expect($audit->auditable_type)->toBe(ComplianceNotification::class)
        ->and($audit->auditable_id)->toBe($notification->id)
        ->and($audit->requirement_id)->toBe('FR-NOT-001');
});

function seedPhase1VerificationDataset(): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'compliance_manager',
        'label' => 'Compliance Manager',
        'permissions' => ['phase1.view', 'phase1.export', 'notifications.plan'],
    ]);

    $role->users()->attach($user);

    $pilot = UasPilot::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Naledi',
        'last_name' => 'Dlamini',
        'email' => 'naledi@example.com',
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; UAS Compliance & Operations Platform FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Remote pilot profile master record for South African UAS operations managed in the VMT platform.',
        'responsible_role' => 'Compliance Manager',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $certificate = PilotCertificate::query()->create([
        'uas_pilot_id' => $pilot->id,
        'certificate_number' => 'RPC-VERIFY-001',
        'issue_date' => now()->subYear()->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
        'last_revalidation_date' => now()->subYear()->toDateString(),
        'post_revalidation_submission_due_at' => now()->addDays(44)->toDateString(),
        'status' => 'valid',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
    ]);

    $aircraft = UasAircraft::query()->create([
        'registration' => 'ZT-VFY',
        'manufacturer' => 'VMT',
        'model' => 'Surveyor One',
        'serial_number' => 'SN-VFY-001',
        'operational_status' => 'active_serviceable',
    ]);

    AircraftRegistration::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'registration_number' => 'ZT-VFY',
        'lifecycle_state' => 'initial',
        'issue_date' => now()->subMonth()->toDateString(),
    ]);

    AircraftApproval::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'approval_type' => 'uasla',
        'approval_number' => 'UASLA-VFY-001',
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'status' => 'valid',
    ]);

    PilotLogEntry::query()->create([
        'uas_pilot_id' => $pilot->id,
        'flight_date' => now()->toDateString(),
        'aircraft_registration' => 'ZT-VFY',
        'operation_type' => 'survey',
        'flight_hours' => 1.25,
    ]);

    AircraftFlightFolio::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'uas_pilot_id' => $pilot->id,
        'flight_date' => now()->toDateString(),
        'folio_reference' => 'FOLIO-VFY-001',
        'flight_hours' => 1.25,
        'battery_cycles' => 2,
        'available_offline' => true,
    ]);

    RegulatoryDocument::query()->create([
        'documentable_type' => PilotCertificate::class,
        'documentable_id' => $certificate->id,
        'category' => 'pilot_certificate',
        'title' => 'RPC evidence',
        'status' => 'controlled',
        'locked' => true,
    ]);

    RegulatoryRequirement::query()->create([
        'requirement_id' => 'FR-REG-001',
        'regulation_part' => 'Part 101',
        'title' => 'Structured regulatory requirement register',
        'requirement_text' => 'Maintain structured version-aware regulatory requirements.',
        'responsible_party' => 'Compliance Manager',
        'applicability' => 'UAS compliance controls',
        'system_control' => 'regulatory_requirements table',
        'effective_date' => '2026-09-09',
        'official_source' => 'Founding FRS',
        'source_version' => 'FRS v1.0',
    ]);

    RegulatoryFee::query()->create([
        'regulation_part' => 'Part 187',
        'transaction_code' => 'UASLA_RENEWAL',
        'description' => 'UASLA renewal fee placeholder pending source verification',
        'effective_from' => '2026-09-09',
        'source' => 'Founding FRS fee-register requirement',
        'source_version' => 'FRS v1.0',
    ]);

    ComplianceFinding::query()->create([
        'compliable_type' => UasAircraft::class,
        'compliable_id' => $aircraft->id,
        'requirement_id' => 'FR-AIR-003',
        'state' => 'attention_required',
        'severity' => 'warning',
        'summary' => 'Approval evidence requires compliance manager review.',
    ]);

    RecordRetentionRule::query()->create([
        'record_category' => 'operator_recordkeeping',
        'regulatory_source' => 'Founding FRS FR-REC-002',
        'source_version' => 'FRS v1.0',
        'effective_date' => '2026-09-09',
        'retention_period' => 'at least five years where applicable',
        'applicability' => 'Part 101 operator recordkeeping controls pending regulatory source verification.',
    ]);

    UasAuditEntry::query()->create([
        'user_id' => $user->id,
        'action' => 'phase1.verification.seeded',
        'auditable_type' => UasPilot::class,
        'auditable_id' => $pilot->id,
        'requirement_id' => 'FR-REC-003',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-REC-003',
        'previous_values' => null,
        'new_values' => ['pilot_id' => $pilot->id],
        'occurred_at' => now(),
    ]);

    return $user;
}

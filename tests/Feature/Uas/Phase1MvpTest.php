<?php

use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Services\AircraftServiceabilityEvaluator;
use App\Domains\Uas\Compliance\Application\Queries\ComplianceDashboardSummary;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Documents\Domain\Models\RegulatoryDocument;
use App\Domains\Uas\FlightFolios\Domain\Models\AircraftFlightFolio;
use App\Domains\Uas\FlightLogs\Domain\Models\PilotLogEntry;
use App\Domains\Uas\FlightLogs\Domain\Services\PilotLogbookSummary;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Notifications\Domain\Services\ExpiryNotificationPlanner;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Pilots\Domain\Services\PilotComplianceEvaluator;
use App\Domains\Uas\Records\Domain\Models\RecordRetentionRule;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;

it('implements the remaining Phase 1 MVP records and derived controls', function () {
    $user = User::factory()->create();

    $pilot = UasPilot::query()->create([
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
        'certificate_number' => 'RPC-9001',
        'issue_date' => now()->subYear()->toDateString(),
        'expiry_date' => now()->addDays(45)->toDateString(),
        'last_revalidation_date' => now()->subYear()->toDateString(),
        'post_revalidation_submission_due_at' => now()->addDays(60)->toDateString(),
        'status' => 'valid',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
    ]);

    $aircraft = UasAircraft::query()->create([
        'registration' => 'ZT-UAS',
        'manufacturer' => 'VMT',
        'model' => 'Surveyor One',
        'serial_number' => 'SN-9001',
        'operational_status' => 'active_serviceable',
    ]);

    AircraftRegistration::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'registration_number' => 'ZT-UAS',
        'lifecycle_state' => 'initial',
        'issue_date' => now()->subMonth()->toDateString(),
    ]);

    AircraftApproval::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'approval_type' => 'uasla',
        'approval_number' => 'UASLA-9001',
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'status' => 'valid',
    ]);

    PilotLogEntry::query()->create([
        'uas_pilot_id' => $pilot->id,
        'flight_date' => now()->toDateString(),
        'aircraft_registration' => 'ZT-UAS',
        'operation_type' => 'survey',
        'flight_hours' => 1.25,
    ]);

    AircraftFlightFolio::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'uas_pilot_id' => $pilot->id,
        'flight_date' => now()->toDateString(),
        'folio_reference' => 'FOLIO-9001',
        'flight_hours' => 1.25,
        'battery_cycles' => 2,
        'available_offline' => true,
    ]);

    RegulatoryDocument::query()->create([
        'documentable_type' => UasPilot::class,
        'documentable_id' => $pilot->id,
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
        'compliable_type' => UasPilot::class,
        'compliable_id' => $pilot->id,
        'requirement_id' => 'FR-PIL-003',
        'state' => 'attention_required',
        'severity' => 'warning',
        'summary' => 'RPC enters revalidation window.',
    ]);

    ComplianceNotification::query()->create([
        'user_id' => $user->id,
        'notifiable_record_type' => PilotCertificate::class,
        'notifiable_record_id' => $certificate->id,
        'requirement_id' => 'FR-NOT-001',
        'notification_type' => 'expiry_alert',
        'subject' => 'RPC expiry alert',
        'message' => 'RPC expires within configured alert window.',
        'due_at' => now()->toDateString(),
    ]);

    RecordRetentionRule::query()->create([
        'record_category' => 'operator_recordkeeping',
        'regulatory_source' => 'Founding FRS FR-REC-002',
        'source_version' => 'FRS v1.0',
        'effective_date' => '2026-09-09',
        'retention_period' => 'at least five years where applicable',
        'applicability' => 'Part 101 operator recordkeeping controls pending regulatory source verification.',
    ]);

    expect(app(PilotComplianceEvaluator::class)->rpcState($certificate))->toBe('expiring')
        ->and(app(PilotComplianceEvaluator::class)->revalidationWindow($certificate)['alert_points'])->toBe([120, 90, 60, 30, 14, 0])
        ->and(app(PilotComplianceEvaluator::class)->submissionDeadline($certificate))->toBe(now()->addDays(60)->toDateString())
        ->and(app(PilotLogbookSummary::class)->summarize($pilot)['total_hours'])->toBe(1.25)
        ->and(app(AircraftServiceabilityEvaluator::class)->mayBeAssignedToReleasedFlight($aircraft))->toBeTrue()
        ->and(app(ExpiryNotificationPlanner::class)->alertDates(now()->addDays(30))[30])->toBe(now()->toDateString())
        ->and(app(ComplianceDashboardSummary::class)->execute()['open_findings'])->toBe(1);
});

<?php

namespace App\Domains\Uas\Compliance\Application\Queries;

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
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

class Phase1VerificationReport
{
    public function execute(): array
    {
        $counts = [
            'roles' => UasRole::query()->count(),
            'pilots' => UasPilot::query()->count(),
            'pilot_certificates' => PilotCertificate::query()->count(),
            'pilot_log_entries' => PilotLogEntry::query()->count(),
            'aircraft' => UasAircraft::query()->count(),
            'aircraft_registrations' => AircraftRegistration::query()->count(),
            'aircraft_approvals' => AircraftApproval::query()->count(),
            'aircraft_flight_folios' => AircraftFlightFolio::query()->count(),
            'regulatory_documents' => RegulatoryDocument::query()->count(),
            'regulatory_requirements' => RegulatoryRequirement::query()->count(),
            'regulatory_fees' => RegulatoryFee::query()->count(),
            'compliance_findings' => ComplianceFinding::query()->count(),
            'compliance_notifications' => ComplianceNotification::query()->count(),
            'retention_rules' => RecordRetentionRule::query()->count(),
            'audit_entries' => UasAuditEntry::query()->count(),
        ];

        return [
            'status' => 'VERIFICATION_READY',
            'generated_at' => now()->toIso8601String(),
            'counts' => $counts,
            'requirements' => $this->requirements($counts),
            'open_findings' => ComplianceFinding::query()
                ->latest()
                ->limit(10)
                ->get(['id', 'requirement_id', 'state', 'severity', 'summary', 'due_at'])
                ->map(fn (ComplianceFinding $finding): array => [
                    'id' => $finding->id,
                    'requirement_id' => $finding->requirement_id,
                    'state' => $finding->state,
                    'severity' => $finding->severity,
                    'summary' => $finding->summary,
                    'due_at' => $finding->due_at?->toDateString(),
                ])
                ->all(),
            'pending_notifications' => ComplianceNotification::query()
                ->where('status', 'pending')
                ->orderBy('due_at')
                ->limit(10)
                ->get(['id', 'requirement_id', 'notification_type', 'subject', 'due_at'])
                ->map(fn (ComplianceNotification $notification): array => [
                    'id' => $notification->id,
                    'requirement_id' => $notification->requirement_id,
                    'notification_type' => $notification->notification_type,
                    'subject' => $notification->subject,
                    'due_at' => $notification->due_at?->toDateString(),
                ])
                ->all(),
        ];
    }

    private function requirements(array $counts): array
    {
        return [
            ['code' => 'FR-ACC-001', 'name' => 'UAS role boundary', 'status' => $counts['roles'] > 0 ? 'verified_ready' : 'needs_seed_data', 'evidence' => 'uas_roles'],
            ['code' => 'FR-PIL-001', 'name' => 'Pilot master profile', 'status' => $counts['pilots'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'uas_pilots'],
            ['code' => 'FR-PIL-002', 'name' => 'RPC compliance state', 'status' => $counts['pilot_certificates'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'pilot_certificates'],
            ['code' => 'FR-LOG-001', 'name' => 'Pilot flight logbook', 'status' => $counts['pilot_log_entries'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'pilot_log_entries'],
            ['code' => 'FR-AIR-001', 'name' => 'Aircraft master record', 'status' => $counts['aircraft'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'uas_aircraft'],
            ['code' => 'FR-AIR-002', 'name' => 'Aircraft registration lifecycle', 'status' => $counts['aircraft_registrations'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'aircraft_registrations'],
            ['code' => 'FR-AIR-003', 'name' => 'Aircraft approval validity', 'status' => $counts['aircraft_approvals'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'aircraft_approvals'],
            ['code' => 'FR-FOL-001', 'name' => 'Aircraft flight folio', 'status' => $counts['aircraft_flight_folios'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'aircraft_flight_folios'],
            ['code' => 'FR-DOC-001', 'name' => 'Controlled document record', 'status' => $counts['regulatory_documents'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'regulatory_documents'],
            ['code' => 'FR-REG-001', 'name' => 'Regulatory requirement register', 'status' => $counts['regulatory_requirements'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'regulatory_requirements'],
            ['code' => 'FR-FEE-001', 'name' => 'Regulatory fee register', 'status' => $counts['regulatory_fees'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'regulatory_fees'],
            ['code' => 'FR-NOT-001', 'name' => 'Compliance notifications', 'status' => $counts['compliance_notifications'] > 0 ? 'verified_ready' : 'needs_planning_run', 'evidence' => 'compliance_notifications'],
            ['code' => 'FR-REC-002', 'name' => 'Retention rules', 'status' => $counts['retention_rules'] > 0 ? 'verified_ready' : 'needs_record', 'evidence' => 'record_retention_rules'],
            ['code' => 'FR-REC-003', 'name' => 'Audit trail', 'status' => $counts['audit_entries'] > 0 ? 'verified_ready' : 'needs_audit_event', 'evidence' => 'uas_audit_entries'],
        ];
    }
}

<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Compliance\Application\Queries\ComplianceTraceabilityReport;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Training\Domain\Models\UasTrainingComplianceLink;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function traceabilityUser(array $permissions = ['regulations.view']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'traceability_viewer_'.str()->random(8),
        'label' => 'Traceability Viewer',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

it('requires regulation view permission for the compliance traceability report', function () {
    $this->withoutVite();

    $user = traceabilityUser([]);

    $this->actingAs($user)->get('/compliance/traceability')->assertForbidden();
});

it('traces automated compliance controls to regulatory or organisational sources and flags gaps', function () {
    $requirement = traceabilityRequirement();
    $course = traceabilityCourse();

    ComplianceFinding::query()->create([
        'requirement_id' => $requirement->requirement_id,
        'state' => 'attention_required',
        'severity' => 'warning',
        'summary' => 'Training evidence requires review.',
    ]);

    ComplianceNotification::query()->create([
        'requirement_id' => 'REG-MISSING-001',
        'notification_type' => 'regulatory_change',
        'status' => 'pending',
        'subject' => 'Unmapped regulatory change',
        'message' => 'Source mapping is missing.',
    ]);

    UasAuditEntry::query()->create([
        'action' => 'operator.manual_training_requirement.created',
        'auditable_type' => UasTrainingCourse::class,
        'auditable_id' => $course->id,
        'requirement_id' => 'FR-OM-004',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-004',
        'new_values' => ['course' => $course->id],
        'occurred_at' => now(),
    ]);

    UasTrainingComplianceLink::query()->create([
        'training_course_id' => $course->id,
        'regulatory_requirement_id' => $requirement->id,
        'source_type' => 'regulatory_requirement',
        'requirement_reference' => $requirement->requirement_id,
        'title' => $requirement->title,
        'responsible_role' => $requirement->responsible_party,
        'applicability' => $requirement->applicability,
        'evidence_required' => $requirement->evidence_required,
        'link_status' => 'required',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-TRN-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Training compliance traceability.',
    ]);

    $report = app(ComplianceTraceabilityReport::class)->execute();

    expect($report['summary']['total_controls'])->toBe(4)
        ->and($report['summary']['traceable_controls'])->toBe(2)
        ->and($report['summary']['source_text_only_controls'])->toBe(1)
        ->and($report['summary']['missing_source_controls'])->toBe(1);

    expect(collect($report['controls'])->where('control_type', 'finding')->first())
        ->toMatchArray([
            'requirement_id' => 'REG-TRACE-001',
            'traceability_status' => 'traceable',
            'regulatory_source' => 'SACAA Part 101 controlled source',
            'source_version' => 'Checked 2026-09-11',
            'responsible_party' => 'Compliance Manager',
        ]);
});

it('exposes the traceability report through Inertia', function () {
    $this->withoutVite();

    $user = traceabilityUser();
    $requirement = traceabilityRequirement();

    ComplianceFinding::query()->create([
        'requirement_id' => $requirement->requirement_id,
        'state' => 'open',
        'severity' => 'warning',
        'summary' => 'Visible traceability finding.',
    ]);

    $this->actingAs($user)
        ->get('/compliance/traceability')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('compliance/traceability')
            ->where('report.summary.total_controls', 1)
            ->where('report.summary.traceable_controls', 1)
            ->where('report.controls.0.requirement_id', 'REG-TRACE-001')
            ->where('report.controls.0.traceability_status', 'traceable')
        );
});

function traceabilityRequirement(): RegulatoryRequirement
{
    return RegulatoryRequirement::query()->create([
        'requirement_id' => 'REG-TRACE-001',
        'regulation_part' => 'Part 101',
        'clause_reference' => '101.Trace',
        'title' => 'Traceable automated control',
        'requirement_text' => 'Automated controls must retain source traceability.',
        'responsible_party' => 'Compliance Manager',
        'applicability' => 'Automated UAS compliance controls',
        'system_control' => 'Compliance traceability report',
        'evidence_required' => 'Finding, audit or training link source record.',
        'effective_date' => '2026-09-11',
        'official_source' => 'SACAA Part 101 controlled source',
        'source_version' => 'Checked 2026-09-11',
        'status' => 'active',
    ]);
}

function traceabilityCourse(): UasTrainingCourse
{
    return UasTrainingCourse::query()->create([
        'code' => 'TRN-TRACE-001',
        'title' => 'Traceability Course',
        'classification' => 'operator_internal_competency',
        'status' => 'active',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-TRN-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Traceability test course.',
    ]);
}

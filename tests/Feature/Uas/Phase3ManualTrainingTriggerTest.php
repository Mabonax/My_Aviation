<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionTrainingReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualTrainingRequirement;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function trainingTriggerUser(array $permissions = ['operators.view', 'operators.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'manual_training_admin_'.str()->random(8),
        'label' => 'Manual Training Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function trainingTriggerOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Training Ops (Pty) Ltd',
        'trading_name' => 'Training Ops',
        'registration_number' => '2026/TRN/001',
        'uasoc_number' => 'UASOC-TRN',
        'certificate_issue_date' => '2026-01-10',
        'certificate_expiry_date' => '2027-01-09',
        'status' => 'active',
        'accountable_manager' => 'Accountable Lead',
        'responsible_person_flight_operations' => 'Flight Ops Lead',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'operating_bases' => ['Midrand Base'],
        'approved_aircraft' => [],
        'approved_pilots' => [],
        'operations_specifications' => ['VLOS inspection'],
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'UAS operator profile, certificate holder identity, post holders, operating bases, approved aircraft, approved pilots and OpsSpecs.',
        'responsible_role' => 'Accountable Manager',
        ...$overrides,
    ]);
}

function trainingTriggerRevision(UasOperator $operator, array $overrides = []): UasOperationsManualRevision
{
    return UasOperationsManualRevision::query()->create([
        'uas_operator_id' => $operator->id,
        'manual_name' => 'VMT Operations Manual',
        'revision_code' => 'OM-REV-TRN',
        'effective_date' => '2026-09-15',
        'approval_status' => 'approved',
        'authority_approval_reference' => 'SACAA-OM-TRN',
        'sections' => ['Emergency Response', 'Flight Operations'],
        'change_summary' => 'Emergency response procedure amendment.',
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Version-controlled Operations Manual revision control with effective date, approval status, authority reference, sections, change summary and superseded revision.',
        ...$overrides,
    ]);
}

function trainingTriggerPayload(array $overrides = []): array
{
    return [
        'title' => 'Emergency response amendment briefing',
        'requirement_type' => 'operator_internal_competency',
        'training_status' => 'required',
        'affected_roles' => ['Remote Pilot', 'Flight Operations Lead'],
        'due_date' => '2026-09-30',
        'competency_standard' => 'Operator ERP briefing completion',
        'trigger_reason' => 'Manual amendment changes emergency response procedures.',
        'evidence_references' => ['training-register-001'],
        'notes' => 'Internal competency briefing before affected personnel operate under the revision.',
        ...$overrides,
    ];
}

function storedTrainingRequirement(UasOperationsManualRevision $revision, array $overrides = []): UasOperationsManualTrainingRequirement
{
    return UasOperationsManualTrainingRequirement::query()->create([
        'manual_revision_id' => $revision->id,
        'title' => 'Visible amendment briefing',
        'requirement_type' => 'safety_briefing',
        'training_status' => 'assigned',
        'affected_roles' => ['Remote Pilot'],
        'due_date' => '2026-09-30',
        'competency_standard' => 'Briefing attendance',
        'trigger_reason' => 'Visible test trigger.',
        'evidence_references' => ['visible-training-001'],
        'notes' => 'Visible note.',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-004; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Operations Manual amendment training trigger for mandatory training and competency requirements.',
        ...$overrides,
    ]);
}

it('requires operator update permission for manual amendment training trigger routes', function () {
    $this->withoutVite();

    $operator = trainingTriggerOperator();
    $revision = trainingTriggerRevision($operator);
    $viewer = trainingTriggerUser(['operators.view']);

    $this->actingAs($viewer)->get("/operations-manual-revisions/{$revision->id}/training-requirements/create")->assertForbidden();
    $this->actingAs($viewer)->post("/operations-manual-revisions/{$revision->id}/training-requirements", trainingTriggerPayload())->assertForbidden();
});

it('creates mandatory training requirements from manual amendments with FR-OM-004 audit evidence', function () {
    $user = trainingTriggerUser();
    $operator = trainingTriggerOperator();
    $revision = trainingTriggerRevision($operator);

    $this->actingAs($user)
        ->post("/operations-manual-revisions/{$revision->id}/training-requirements", trainingTriggerPayload())
        ->assertRedirect(route('operations-manual-revisions.show', $revision));

    $requirement = UasOperationsManualTrainingRequirement::query()->firstOrFail();

    expect($requirement->title)->toBe('Emergency response amendment briefing')
        ->and($requirement->requirement_type)->toBe('operator_internal_competency')
        ->and($requirement->training_status)->toBe('required')
        ->and($requirement->affected_roles)->toBe(['Remote Pilot', 'Flight Operations Lead'])
        ->and($requirement->regulatory_source)->toContain('FR-OM-004')
        ->and($requirement->created_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.manual_training_requirement.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasOperationsManualTrainingRequirement::class)
        ->and($audit->auditable_id)->toBe($requirement->id)
        ->and($audit->requirement_id)->toBe('FR-OM-004');
});

it('validates training trigger type status roles and evidence values', function () {
    $user = trainingTriggerUser();
    $operator = trainingTriggerOperator();
    $revision = trainingTriggerRevision($operator);

    $this->actingAs($user)
        ->post("/operations-manual-revisions/{$revision->id}/training-requirements", trainingTriggerPayload([
            'title' => '',
            'requirement_type' => 'external_lms_only',
            'training_status' => 'unknown',
            'affected_roles' => [str_repeat('x', 170)],
            'evidence_references' => [str_repeat('x', 510)],
        ]))
        ->assertInvalid(['title', 'requirement_type', 'training_status', 'affected_roles.0', 'evidence_references.0']);
});

it('exposes training trigger creation page and manual revision training report through Inertia', function () {
    $this->withoutVite();

    $user = trainingTriggerUser();
    $operator = trainingTriggerOperator(['legal_entity' => 'Visible Training Operator']);
    $revision = trainingTriggerRevision($operator, ['revision_code' => 'OM-REV-VISIBLE-TRN']);
    storedTrainingRequirement($revision);

    $this->actingAs($user)
        ->get("/operations-manual-revisions/{$revision->id}/training-requirements/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/training-requirements/create')
            ->has('options.types.operator_internal_competency')
            ->has('options.statuses.required')
            ->where('manualRevision.revision_code', 'OM-REV-VISIBLE-TRN')
        );

    $report = app(ManualRevisionTrainingReport::class)->execute($revision);

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['assigned'])->toBe(1)
        ->and($report['requirements'][0]['title'])->toBe('Visible amendment briefing');

    $this->actingAs($user)
        ->get("/operations-manual-revisions/{$revision->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/show')
            ->where('trainingReport.summary.total', 1)
            ->where('trainingReport.requirements.0.title', 'Visible amendment briefing')
        );
});

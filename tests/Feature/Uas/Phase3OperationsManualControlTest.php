<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Application\Queries\OperatorManualRevisionReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function manualUser(array $permissions = ['operators.view', 'operators.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'manual_revision_admin_'.str()->random(8),
        'label' => 'Manual Revision Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function manualOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Manual Ops (Pty) Ltd',
        'trading_name' => 'Manual Ops',
        'registration_number' => '2026/MAN/001',
        'uasoc_number' => 'UASOC-MAN',
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

function manualPayload(array $overrides = []): array
{
    return [
        'manual_name' => 'VMT Operations Manual',
        'revision_code' => 'OM-REV-001',
        'effective_date' => '2026-09-15',
        'approval_status' => 'internal_review',
        'authority_approval_reference' => 'SACAA-OM-2026-001',
        'sections' => ['General', 'Flight Operations', 'Emergency Response'],
        'change_summary' => 'Initial controlled Operations Manual baseline for Phase 3 governance.',
        'superseded_revision_id' => null,
        'evidence_references' => ['manual-register-001', 'authority-correspondence-001'],
        ...$overrides,
    ];
}

function storedManualRevision(UasOperator $operator, array $overrides = []): UasOperationsManualRevision
{
    return UasOperationsManualRevision::query()->create([
        'uas_operator_id' => $operator->id,
        'manual_name' => 'VMT Operations Manual',
        'revision_code' => 'OM-REV-000',
        'effective_date' => '2026-08-01',
        'approval_status' => 'approved',
        'authority_approval_reference' => 'SACAA-OM-2026-000',
        'sections' => ['General'],
        'change_summary' => 'Previous approved revision.',
        'evidence_references' => ['manual-register-000'],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Version-controlled Operations Manual revision control with effective date, approval status, authority reference, sections, change summary and superseded revision.',
        ...$overrides,
    ]);
}

it('requires operator update permission for controlled manual revision routes', function () {
    $this->withoutVite();

    $operator = manualOperator();
    $revision = storedManualRevision($operator);
    $viewer = manualUser(['operators.view']);

    $this->actingAs($viewer)->get("/operators/{$operator->id}/manual-revisions/create")->assertForbidden();
    $this->actingAs($viewer)->post("/operators/{$operator->id}/manual-revisions", manualPayload())->assertForbidden();
    $this->actingAs($viewer)->get("/operations-manual-revisions/{$revision->id}")->assertOk();
    $this->actingAs($viewer)->get("/operations-manual-revisions/{$revision->id}/edit")->assertForbidden();
    $this->actingAs($viewer)->put("/operations-manual-revisions/{$revision->id}", manualPayload())->assertForbidden();
});

it('creates a controlled manual revision with FR-OM-001 traceability and audit evidence', function () {
    $user = manualUser();
    $operator = manualOperator();

    $this->actingAs($user)
        ->post("/operators/{$operator->id}/manual-revisions", manualPayload())
        ->assertRedirect();

    $revision = UasOperationsManualRevision::query()->firstOrFail();

    expect($revision->manual_name)->toBe('VMT Operations Manual')
        ->and($revision->revision_code)->toBe('OM-REV-001')
        ->and($revision->approval_status)->toBe('internal_review')
        ->and($revision->sections)->toBe(['General', 'Flight Operations', 'Emergency Response'])
        ->and($revision->evidence_references)->toBe(['manual-register-001', 'authority-correspondence-001'])
        ->and($revision->regulatory_source)->toContain('FR-OM-001')
        ->and($revision->created_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.manual_revision.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasOperationsManualRevision::class)
        ->and($audit->auditable_id)->toBe($revision->id)
        ->and($audit->requirement_id)->toBe('FR-OM-001');
});

it('marks the referenced manual revision as superseded when a new revision replaces it', function () {
    $user = manualUser();
    $operator = manualOperator();
    $oldRevision = storedManualRevision($operator, ['revision_code' => 'OM-REV-000', 'approval_status' => 'approved']);

    $this->actingAs($user)
        ->post("/operators/{$operator->id}/manual-revisions", manualPayload([
            'revision_code' => 'OM-REV-002',
            'approval_status' => 'approved',
            'superseded_revision_id' => $oldRevision->id,
        ]))
        ->assertRedirect();

    $oldRevision->refresh();

    expect($oldRevision->approval_status)->toBe('superseded')
        ->and(UasOperationsManualRevision::query()->where('revision_code', 'OM-REV-002')->firstOrFail()->superseded_revision_id)->toBe($oldRevision->id);
});

it('updates manual revision approval details and records an audit trail', function () {
    $user = manualUser();
    $operator = manualOperator();
    $revision = storedManualRevision($operator, ['revision_code' => 'OM-REV-001', 'approval_status' => 'internal_review']);

    $this->actingAs($user)
        ->put("/operations-manual-revisions/{$revision->id}", manualPayload([
            'revision_code' => 'OM-REV-001',
            'approval_status' => 'approved',
            'authority_approval_reference' => 'SACAA-OM-APPROVED-001',
            'sections' => ['General', 'Flight Operations'],
        ]))
        ->assertRedirect(route('operations-manual-revisions.show', $revision));

    $revision->refresh();

    expect($revision->approval_status)->toBe('approved')
        ->and($revision->authority_approval_reference)->toBe('SACAA-OM-APPROVED-001')
        ->and($revision->sections)->toBe(['General', 'Flight Operations'])
        ->and($revision->updated_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.manual_revision.updated')->firstOrFail();

    expect($audit->requirement_id)->toBe('FR-OM-001')
        ->and($audit->previous_values['approval_status'])->toBe('internal_review')
        ->and($audit->new_values['approval_status'])->toBe('approved');
});

it('validates controlled manual identity status sections and duplicate revision code per manual', function () {
    $user = manualUser();
    $operator = manualOperator();
    storedManualRevision($operator, ['manual_name' => 'VMT Operations Manual', 'revision_code' => 'OM-REV-001']);

    $this->actingAs($user)
        ->post("/operators/{$operator->id}/manual-revisions", manualPayload([
            'manual_name' => '',
            'approval_status' => 'pending_committee',
            'sections' => [str_repeat('x', 250)],
        ]))
        ->assertInvalid(['manual_name', 'approval_status', 'sections.0']);

    $this->actingAs($user)
        ->post("/operators/{$operator->id}/manual-revisions", manualPayload([
            'manual_name' => 'VMT Operations Manual',
            'revision_code' => 'OM-REV-001',
        ]))
        ->assertInvalid(['revision_code']);
});

it('exposes manual revision pages and operator manual report through Inertia', function () {
    $this->withoutVite();

    $user = manualUser();
    $operator = manualOperator(['legal_entity' => 'Visible Manual Operator']);
    $revision = storedManualRevision($operator, ['revision_code' => 'OM-REV-VISIBLE']);

    $this->actingAs($user)
        ->get("/operators/{$operator->id}/manual-revisions/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/create')
            ->has('options.approval_statuses.approved')
            ->where('operator.legal_entity', 'Visible Manual Operator')
        );

    $this->actingAs($user)
        ->get("/operations-manual-revisions/{$revision->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/show')
            ->where('manualRevision.revision_code', 'OM-REV-VISIBLE')
        );

    $this->actingAs($user)
        ->get("/operations-manual-revisions/{$revision->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/edit')
            ->where('manualRevision.revision_code', 'OM-REV-VISIBLE')
        );

    $report = app(OperatorManualRevisionReport::class)->execute($operator);

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['approved'])->toBe(1)
        ->and($report['revisions'][0]['revision_code'])->toBe('OM-REV-VISIBLE');

    $this->actingAs($user)
        ->get("/operators/{$operator->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/show')
            ->where('manualRevisions.summary.total', 1)
            ->where('manualRevisions.revisions.0.revision_code', 'OM-REV-VISIBLE')
        );
});


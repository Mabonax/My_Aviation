<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionDistributionReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualDistribution;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function distributionUser(array $permissions = ['operators.view', 'operators.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'manual_distribution_admin_'.str()->random(8),
        'label' => 'Manual Distribution Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function distributionOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Distribution Ops (Pty) Ltd',
        'trading_name' => 'Distribution Ops',
        'registration_number' => '2026/DIST/001',
        'uasoc_number' => 'UASOC-DIST',
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

function distributionRevision(UasOperator $operator, array $overrides = []): UasOperationsManualRevision
{
    return UasOperationsManualRevision::query()->create([
        'uas_operator_id' => $operator->id,
        'manual_name' => 'VMT Operations Manual',
        'revision_code' => 'OM-REV-DIST',
        'effective_date' => '2026-09-15',
        'approval_status' => 'approved',
        'authority_approval_reference' => 'SACAA-OM-DIST',
        'sections' => ['General', 'Flight Operations'],
        'change_summary' => 'Distribution controlled revision.',
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Version-controlled Operations Manual revision control with effective date, approval status, authority reference, sections, change summary and superseded revision.',
        ...$overrides,
    ]);
}

function distributionPayload(array $overrides = []): array
{
    return [
        'recipient_name' => 'John Mabona',
        'recipient_role' => 'Accountable Manager',
        'recipient_email' => 'john@example.test',
        'distribution_channel' => 'email',
        'distribution_status' => 'required',
        'required_by' => '2026-09-20',
        'distributed_at' => null,
        'evidence_references' => ['manual-distribution-register-001'],
        'notes' => 'Required recipient for approved Operations Manual revision.',
        ...$overrides,
    ];
}

function storedDistribution(UasOperationsManualRevision $revision, array $overrides = []): UasOperationsManualDistribution
{
    return UasOperationsManualDistribution::query()->create([
        'manual_revision_id' => $revision->id,
        'recipient_name' => 'Visible Recipient',
        'recipient_role' => 'Flight Operations Lead',
        'recipient_email' => 'visible@example.test',
        'distribution_channel' => 'document_portal',
        'distribution_status' => 'distributed',
        'required_by' => '2026-09-20',
        'distributed_at' => '2026-09-16 08:30:00',
        'evidence_references' => ['portal-delivery-001'],
        'notes' => 'Distributed through portal.',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-002; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Operations Manual revision distribution record for personnel required to receive a controlled manual revision.',
        ...$overrides,
    ]);
}

it('requires operator update permission for manual distribution routes', function () {
    $this->withoutVite();

    $operator = distributionOperator();
    $revision = distributionRevision($operator);
    $viewer = distributionUser(['operators.view']);

    $this->actingAs($viewer)->get("/operations-manual-revisions/{$revision->id}/distributions/create")->assertForbidden();
    $this->actingAs($viewer)->post("/operations-manual-revisions/{$revision->id}/distributions", distributionPayload())->assertForbidden();
});

it('records required manual recipients with FR-OM-002 traceability and audit evidence', function () {
    $user = distributionUser();
    $operator = distributionOperator();
    $revision = distributionRevision($operator);

    $this->actingAs($user)
        ->post("/operations-manual-revisions/{$revision->id}/distributions", distributionPayload())
        ->assertRedirect(route('operations-manual-revisions.show', $revision));

    $distribution = UasOperationsManualDistribution::query()->firstOrFail();

    expect($distribution->recipient_name)->toBe('John Mabona')
        ->and($distribution->recipient_role)->toBe('Accountable Manager')
        ->and($distribution->distribution_channel)->toBe('email')
        ->and($distribution->distribution_status)->toBe('required')
        ->and($distribution->evidence_references)->toBe(['manual-distribution-register-001'])
        ->and($distribution->regulatory_source)->toContain('FR-OM-002')
        ->and($distribution->created_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.manual_distribution.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasOperationsManualDistribution::class)
        ->and($audit->auditable_id)->toBe($distribution->id)
        ->and($audit->requirement_id)->toBe('FR-OM-002');
});

it('validates required recipient identity distribution values and duplicates per revision role', function () {
    $user = distributionUser();
    $operator = distributionOperator();
    $revision = distributionRevision($operator);
    storedDistribution($revision, ['recipient_name' => 'John Mabona', 'recipient_role' => 'Accountable Manager']);

    $this->actingAs($user)
        ->post("/operations-manual-revisions/{$revision->id}/distributions", distributionPayload([
            'recipient_name' => '',
            'recipient_role' => '',
            'recipient_email' => 'not-an-email',
            'distribution_channel' => 'fax',
            'distribution_status' => 'unknown',
            'evidence_references' => [str_repeat('x', 510)],
        ]))
        ->assertInvalid(['recipient_name', 'recipient_role', 'recipient_email', 'distribution_channel', 'distribution_status', 'evidence_references.0']);

    $this->actingAs($user)
        ->post("/operations-manual-revisions/{$revision->id}/distributions", distributionPayload([
            'recipient_name' => 'John Mabona',
            'recipient_role' => 'Accountable Manager',
        ]))
        ->assertInvalid(['recipient_name']);
});

it('exposes distribution creation page and manual revision distribution report through Inertia', function () {
    $this->withoutVite();

    $user = distributionUser();
    $operator = distributionOperator(['legal_entity' => 'Visible Distribution Operator']);
    $revision = distributionRevision($operator, ['revision_code' => 'OM-REV-VISIBLE-DIST']);
    storedDistribution($revision);

    $this->actingAs($user)
        ->get("/operations-manual-revisions/{$revision->id}/distributions/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/distributions/create')
            ->has('options.channels.email')
            ->has('options.statuses.distributed')
            ->where('manualRevision.revision_code', 'OM-REV-VISIBLE-DIST')
        );

    $report = app(ManualRevisionDistributionReport::class)->execute($revision);

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['distributed'])->toBe(1)
        ->and($report['distributions'][0]['recipient_name'])->toBe('Visible Recipient');

    $this->actingAs($user)
        ->get("/operations-manual-revisions/{$revision->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/show')
            ->where('distributionReport.summary.total', 1)
            ->where('distributionReport.distributions.0.recipient_name', 'Visible Recipient')
        );
});

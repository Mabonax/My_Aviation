<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionDistributionReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualDistribution;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function acknowledgementManager(array $permissions = ['operators.view', 'operators.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'manual_acknowledgement_admin_'.str()->random(8),
        'label' => 'Manual Acknowledgement Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function acknowledgementRecipient(string $email = 'recipient@example.test'): User
{
    return User::factory()->create(['email' => $email]);
}

function acknowledgementOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Acknowledgement Ops (Pty) Ltd',
        'trading_name' => 'Acknowledgement Ops',
        'registration_number' => '2026/ACK/001',
        'uasoc_number' => 'UASOC-ACK',
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

function acknowledgementRevision(UasOperator $operator, array $overrides = []): UasOperationsManualRevision
{
    return UasOperationsManualRevision::query()->create([
        'uas_operator_id' => $operator->id,
        'manual_name' => 'VMT Operations Manual',
        'revision_code' => 'OM-REV-ACK',
        'effective_date' => '2026-09-15',
        'approval_status' => 'approved',
        'authority_approval_reference' => 'SACAA-OM-ACK',
        'sections' => ['General', 'Flight Operations'],
        'change_summary' => 'Acknowledgement controlled revision.',
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Version-controlled Operations Manual revision control with effective date, approval status, authority reference, sections, change summary and superseded revision.',
        ...$overrides,
    ]);
}

function acknowledgementDistribution(UasOperationsManualRevision $revision, array $overrides = []): UasOperationsManualDistribution
{
    return UasOperationsManualDistribution::query()->create([
        'manual_revision_id' => $revision->id,
        'recipient_name' => 'Recipient User',
        'recipient_role' => 'Remote Pilot',
        'recipient_email' => 'recipient@example.test',
        'distribution_channel' => 'email',
        'distribution_status' => 'distributed',
        'acknowledgement_status' => 'pending',
        'required_by' => '2026-09-20',
        'distributed_at' => '2026-09-16 08:30:00',
        'evidence_references' => ['email-delivery-001'],
        'notes' => 'Distributed by compliance.',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-002; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Operations Manual revision distribution record for personnel required to receive a controlled manual revision.',
        ...$overrides,
    ]);
}

it('allows the affected recipient to open and record acknowledgement without operator role permissions', function () {
    $this->withoutVite();

    $operator = acknowledgementOperator();
    $revision = acknowledgementRevision($operator);
    $distribution = acknowledgementDistribution($revision);
    $recipient = acknowledgementRecipient('recipient@example.test');

    $this->actingAs($recipient)
        ->get("/operations-manual-distributions/{$distribution->id}/acknowledge")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/distributions/acknowledge')
            ->where('distribution.recipient_email', 'recipient@example.test')
            ->where('manualRevision.revision_code', 'OM-REV-ACK')
        );

    $this->actingAs($recipient)
        ->put("/operations-manual-distributions/{$distribution->id}/acknowledge", [
            'readership_confirmed' => true,
            'acknowledgement_notes' => 'Read and understood.',
        ])
        ->assertRedirect(route('operations-manual-revisions.show', $revision));

    $distribution->refresh();

    expect($distribution->acknowledgement_status)->toBe('acknowledged')
        ->and($distribution->acknowledged_by)->toBe($recipient->id)
        ->and($distribution->acknowledged_at)->not->toBeNull()
        ->and($distribution->acknowledgement_statement)->toBe('I acknowledge receipt and readership of this Operations Manual revision.')
        ->and($distribution->acknowledgement_notes)->toBe('Read and understood.');

    $audit = UasAuditEntry::query()->where('action', 'operator.manual_acknowledgement.recorded')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasOperationsManualDistribution::class)
        ->and($audit->auditable_id)->toBe($distribution->id)
        ->and($audit->requirement_id)->toBe('FR-OM-003');
});

it('blocks non-recipient users without operator update permission from acknowledgement records', function () {
    $operator = acknowledgementOperator();
    $revision = acknowledgementRevision($operator);
    $distribution = acknowledgementDistribution($revision, ['recipient_email' => 'assigned@example.test']);
    $outsider = acknowledgementRecipient('outsider@example.test');

    $this->actingAs($outsider)->get("/operations-manual-distributions/{$distribution->id}/acknowledge")->assertForbidden();
    $this->actingAs($outsider)->put("/operations-manual-distributions/{$distribution->id}/acknowledge", ['readership_confirmed' => true])->assertForbidden();
});

it('requires explicit receipt and readership confirmation before acknowledgement is recorded', function () {
    $operator = acknowledgementOperator();
    $revision = acknowledgementRevision($operator);
    $distribution = acknowledgementDistribution($revision);
    $recipient = acknowledgementRecipient('recipient@example.test');

    $this->actingAs($recipient)
        ->put("/operations-manual-distributions/{$distribution->id}/acknowledge", [
            'readership_confirmed' => false,
        ])
        ->assertInvalid(['readership_confirmed']);

    expect($distribution->refresh()->acknowledgement_status)->toBe('pending');
});

it('allows operator managers to record acknowledgements and exposes acknowledgement summary in the revision report', function () {
    $this->withoutVite();

    $manager = acknowledgementManager();
    $operator = acknowledgementOperator(['legal_entity' => 'Visible Acknowledgement Operator']);
    $revision = acknowledgementRevision($operator, ['revision_code' => 'OM-REV-VISIBLE-ACK']);
    $distribution = acknowledgementDistribution($revision, ['recipient_name' => 'Visible Recipient']);

    $this->actingAs($manager)
        ->put("/operations-manual-distributions/{$distribution->id}/acknowledge", [
            'readership_confirmed' => true,
        ])
        ->assertRedirect(route('operations-manual-revisions.show', $revision));

    $report = app(ManualRevisionDistributionReport::class)->execute($revision);

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['acknowledged'])->toBe(1)
        ->and($report['summary']['acknowledgement_pending'])->toBe(0)
        ->and($report['distributions'][0]['acknowledgement_status'])->toBe('acknowledged');

    $this->actingAs($manager)
        ->get("/operations-manual-revisions/{$revision->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/manual-revisions/show')
            ->where('distributionReport.summary.acknowledged', 1)
            ->where('distributionReport.distributions.0.acknowledgement_status', 'acknowledged')
        );
});

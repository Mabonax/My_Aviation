<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Application\Queries\OperatorCertificateCaseReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function certificateUser(array $permissions = ['operators.view', 'operators.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'certificate_case_admin_'.str()->random(8),
        'label' => 'Certificate Case Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function certificateOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Certificate Ops (Pty) Ltd',
        'trading_name' => 'Certificate Ops',
        'registration_number' => '2026/CERT/001',
        'uasoc_number' => 'UASOC-CERT',
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

function certificatePayload(array $overrides = []): array
{
    return [
        'case_type' => 'renewal',
        'status' => 'evidence_collection',
        'deadline_at' => '2026-12-10',
        'evidence_requirements' => ['Updated Ops Manual', 'Fleet list', 'Post holder letters'],
        'outstanding_documents' => ['Updated Ops Manual'],
        'fleet_scope' => ['ZU-CERT1'],
        'personnel_scope' => ['Accountable Lead', 'Flight Ops Lead'],
        'ops_spec_scope' => ['VLOS inspection renewal'],
        'operations_manual_revision' => 'OM-REV-2026-09',
        'fees' => ['Renewal fee captured'],
        'authority_correspondence' => ['SACAA pre-submission email logged'],
        'outcome' => null,
        'submitted_at' => null,
        'decided_at' => null,
        ...$overrides,
    ];
}

function storedCertificateCase(UasOperator $operator, array $overrides = []): UasOperatorCertificateCase
{
    return UasOperatorCertificateCase::query()->create([
        'uas_operator_id' => $operator->id,
        'case_number' => 'OPS-REN-20260911-0001',
        'case_type' => 'renewal',
        'status' => 'evidence_collection',
        'deadline_at' => '2026-12-10',
        'evidence_requirements' => ['Updated Ops Manual'],
        'outstanding_documents' => ['Updated Ops Manual'],
        'fleet_scope' => ['ZU-CERT1'],
        'personnel_scope' => ['Accountable Lead'],
        'ops_spec_scope' => ['VLOS inspection renewal'],
        'operations_manual_revision' => 'OM-REV-2026-09',
        'fees' => ['Renewal fee captured'],
        'submission_status' => 'not_ready',
        'authority_correspondence' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-002; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'UASOC/ROC application, amendment and renewal case management with evidence, submission, authority correspondence and outcome tracking.',
        ...$overrides,
    ]);
}

it('requires operator update permission for certificate lifecycle case routes', function () {
    $this->withoutVite();

    $operator = certificateOperator();
    $case = storedCertificateCase($operator);
    $viewer = certificateUser(['operators.view']);

    $this->actingAs($viewer)->get("/operators/{$operator->id}/certificate-cases/create")->assertForbidden();
    $this->actingAs($viewer)->post("/operators/{$operator->id}/certificate-cases", certificatePayload())->assertForbidden();
    $this->actingAs($viewer)->get("/operator-certificate-cases/{$case->id}")->assertOk();
    $this->actingAs($viewer)->get("/operator-certificate-cases/{$case->id}/edit")->assertForbidden();
    $this->actingAs($viewer)->put("/operator-certificate-cases/{$case->id}", certificatePayload())->assertForbidden();
});

it('creates a renewal case with derived submission status and audit evidence', function () {
    $user = certificateUser();
    $operator = certificateOperator();

    $this->actingAs($user)
        ->post("/operators/{$operator->id}/certificate-cases", certificatePayload())
        ->assertRedirect();

    $case = UasOperatorCertificateCase::query()->firstOrFail();

    expect($case->case_number)->toStartWith('OPS-REN-')
        ->and($case->case_type)->toBe('renewal')
        ->and($case->status)->toBe('evidence_collection')
        ->and($case->submission_status)->toBe('not_ready')
        ->and($case->outstanding_documents)->toBe(['Updated Ops Manual'])
        ->and($case->regulatory_source)->toContain('FR-OPS-002')
        ->and($case->opened_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.certificate_case.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasOperatorCertificateCase::class)
        ->and($audit->auditable_id)->toBe($case->id)
        ->and($audit->requirement_id)->toBe('FR-OPS-002');
});

it('updates lifecycle cases and recalculates submission status', function () {
    $user = certificateUser();
    $operator = certificateOperator();
    $case = storedCertificateCase($operator);

    $this->actingAs($user)
        ->put("/operator-certificate-cases/{$case->id}", certificatePayload([
            'case_number' => $case->case_number,
            'status' => 'ready_for_submission',
            'outstanding_documents' => [],
            'authority_correspondence' => ['Submission pack reviewed internally'],
        ]))
        ->assertRedirect(route('operator-certificate-cases.show', $case));

    $case->refresh();

    expect($case->status)->toBe('ready_for_submission')
        ->and($case->submission_status)->toBe('ready')
        ->and($case->authority_correspondence)->toBe(['Submission pack reviewed internally'])
        ->and($case->updated_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.certificate_case.updated')->firstOrFail();

    expect($audit->requirement_id)->toBe('FR-OPS-002')
        ->and($audit->previous_values['status'])->toBe('evidence_collection')
        ->and($audit->new_values['status'])->toBe('ready_for_submission');
});

it('validates certificate case type status and list payloads', function () {
    $user = certificateUser();
    $operator = certificateOperator();

    $this->actingAs($user)
        ->post("/operators/{$operator->id}/certificate-cases", certificatePayload([
            'case_type' => 'extension',
            'status' => 'unknown',
            'evidence_requirements' => [str_repeat('x', 250)],
        ]))
        ->assertInvalid(['case_type', 'status', 'evidence_requirements.0']);
});

it('exposes certificate case pages and operator case report through Inertia', function () {
    $this->withoutVite();

    $user = certificateUser();
    $operator = certificateOperator(['legal_entity' => 'Visible Certificate Operator']);
    $case = storedCertificateCase($operator, ['case_number' => 'OPS-REN-VISIBLE']);

    $this->actingAs($user)
        ->get("/operators/{$operator->id}/certificate-cases/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/certificate-cases/create')
            ->has('options.types.renewal')
            ->where('operator.legal_entity', 'Visible Certificate Operator')
        );

    $this->actingAs($user)
        ->get("/operator-certificate-cases/{$case->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/certificate-cases/show')
            ->where('certificateCase.case_number', 'OPS-REN-VISIBLE')
        );

    $this->actingAs($user)
        ->get("/operator-certificate-cases/{$case->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/certificate-cases/edit')
            ->where('certificateCase.case_number', 'OPS-REN-VISIBLE')
        );

    $report = app(OperatorCertificateCaseReport::class)->execute($operator);

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['open'])->toBe(1)
        ->and($report['cases'][0]['case_number'])->toBe('OPS-REN-VISIBLE');

    $this->actingAs($user)
        ->get("/operators/{$operator->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/show')
            ->where('certificateCases.summary.total', 1)
            ->where('certificateCases.cases.0.case_number', 'OPS-REN-VISIBLE')
        );
});
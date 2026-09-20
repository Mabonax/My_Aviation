<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Application\Queries\ApplicationRenewalPackReport;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function applicationPackUser(array $permissions = ['operators.view'], ?UasOperator $operator = null): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'application_pack_viewer_'.str()->random(8),
        'label' => 'Application Pack Viewer',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    if ($operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => UasOperatorMembership::ROLE_REMOTE_PILOT,
            'status' => UasOperatorMembership::STATUS_ACTIVE,
            'source' => UasOperatorMembership::SOURCE_ADMIN,
            'activated_at' => now(),
        ]);
    }

    return $user;
}

function applicationPackOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Application Pack Ops (Pty) Ltd',
        'trading_name' => 'Application Pack Ops',
        'registration_number' => '2026/PACK/001',
        'uasoc_number' => 'UASOC-PACK',
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

function applicationPackCase(UasOperator $operator, array $overrides = []): UasOperatorCertificateCase
{
    return UasOperatorCertificateCase::query()->create([
        'uas_operator_id' => $operator->id,
        'case_number' => 'OPS-REN-PACK',
        'case_type' => 'renewal',
        'status' => 'evidence_collection',
        'deadline_at' => '2026-12-10',
        'evidence_requirements' => ['Updated Ops Manual', 'Fleet list', 'Insurance'],
        'outstanding_documents' => ['Updated Ops Manual'],
        'fleet_scope' => ['ZU-PACK1'],
        'personnel_scope' => ['Accountable Lead'],
        'ops_spec_scope' => ['VLOS inspection renewal'],
        'operations_manual_revision' => 'OM-REV-2026-09',
        'fees' => [],
        'submission_status' => 'not_ready',
        'authority_correspondence' => ['SACAA pre-submission email logged'],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-002; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'UASOC/ROC application, amendment and renewal case management with evidence, submission, authority correspondence and outcome tracking.',
        ...$overrides,
    ]);
}

it('requires operator view permission for application and renewal packs', function () {
    $this->withoutVite();

    $operator = applicationPackOperator();
    $case = applicationPackCase($operator);
    $user = applicationPackUser([]);

    $this->actingAs($user)
        ->get("/operator-certificate-cases/{$case->id}/application-pack")
        ->assertForbidden();
});

it('builds renewal pack readiness with current forms fees checklist and evidence index', function () {
    $operator = applicationPackOperator();
    $case = applicationPackCase($operator);

    RegulatoryForm::query()->create([
        'form_code' => 'SACAA-UAS-REN',
        'form_title' => 'UASOC renewal application',
        'regulatory_area' => 'Part 101',
        'revision' => '2026-01',
        'effective_date' => '2026-09-11',
        'source_reference' => 'SACAA controlled form source pending verification',
        'required_transaction' => 'UASOC renewal',
        'status' => 'active',
    ]);

    RegulatoryFee::query()->create([
        'regulation_part' => 'Part 101',
        'transaction_code' => 'UASLA_RENEWAL',
        'description' => 'UASLA renewal fee',
        'amount' => '950.00',
        'currency' => 'ZAR',
        'effective_from' => '2026-04-01',
        'source' => 'SACAA fee schedule pending source verification',
        'source_version' => 'SACAA-FEE-2026',
        'status' => 'active',
    ]);

    $pack = app(ApplicationRenewalPackReport::class)->execute($case);

    expect($pack['requirement_id'])->toBe('FR-PACK-001')
        ->and($pack['cover_sheet']['title'])->toBe('RENEWAL PACK')
        ->and($pack['required_forms'][0]['form_code'])->toBe('SACAA-UAS-REN')
        ->and($pack['applicable_fees'][0]['transaction_code'])->toBe('UASLA_RENEWAL')
        ->and($pack['readiness']['score'])->toBe(83)
        ->and($pack['checklist'][0]['complete'])->toBeFalse()
        ->and($pack['evidence_index'][0]['items'])->toBe(['ZU-PACK1']);
});

it('exposes the application pack through Inertia and links from the case page', function () {
    $this->withoutVite();

    $operator = applicationPackOperator(['legal_entity' => 'Visible Pack Operator']);
    $user = applicationPackUser([], $operator);
    $case = applicationPackCase($operator);

    RegulatoryForm::query()->create([
        'form_code' => 'SACAA-UAS-APP',
        'form_title' => 'UASOC renewal application',
        'regulatory_area' => 'Part 101',
        'revision' => '2026-01',
        'effective_date' => '2026-09-11',
        'source_reference' => 'SACAA controlled form source pending verification',
        'required_transaction' => 'UASOC renewal',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get("/operator-certificate-cases/{$case->id}/application-pack")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/certificate-cases/application-pack')
            ->where('pack.requirement_id', 'FR-PACK-001')
            ->where('pack.cover_sheet.operator', 'Visible Pack Operator')
            ->where('pack.required_forms.0.form_code', 'SACAA-UAS-APP')
        );

    $this->actingAs($user)
        ->get("/operator-certificate-cases/{$case->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/certificate-cases/show')
            ->where('certificateCase.case_number', 'OPS-REN-PACK')
        );
});

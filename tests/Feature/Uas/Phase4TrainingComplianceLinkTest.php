<?php

use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Training\Domain\Models\UasTrainingComplianceLink;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function complianceLinkUser(array $permissions = ['training.view', 'training.create', 'training.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'training_link_admin_'.str()->random(8),
        'label' => 'Training Link Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function complianceLinkPayload(array $overrides = []): array
{
    return [
        'code' => 'TRN-LINK-001',
        'title' => 'Compliance Link Course',
        'classification' => 'operator_internal_competency',
        'status' => 'active',
        'summary' => 'Operator competency course with compliance linkage.',
        'authority_approval_reference' => null,
        'modules' => ['Compliance foundation'],
        'lessons' => ['Requirement traceability'],
        'resources' => ['Compliance matrix extract'],
        'assessments' => ['Compliance knowledge check'],
        'competencies' => ['Compliance link understood'],
        'competency_records' => ['Remote Pilot A'],
        'compliance_links' => ['ORG-COMP-001'],
        ...$overrides,
    ];
}

it('links organisational requirements to required competencies and retained training records with FR-TRN-002 audit evidence', function () {
    $user = complianceLinkUser();

    $this->actingAs($user)
        ->post('/training-courses', complianceLinkPayload([
            'code' => 'TRN-COMP-001',
            'title' => 'Security Awareness Competency',
            'competencies' => ['Security awareness completed'],
            'competency_records' => ['Remote Pilot Security File'],
            'compliance_links' => ['ORG-SEC-TRAIN-001'],
        ]))
        ->assertRedirect();

    $course = UasTrainingCourse::query()->with(['competencies', 'competencyRecords', 'complianceLinks'])->where('code', 'TRN-COMP-001')->firstOrFail();
    $link = UasTrainingComplianceLink::query()->firstOrFail();

    expect($course->complianceLinks)->toHaveCount(1)
        ->and($link->training_course_id)->toBe($course->id)
        ->and($link->training_competency_id)->toBe($course->competencies->first()->id)
        ->and($link->training_competency_record_id)->toBe($course->competencyRecords->first()->id)
        ->and($link->source_type)->toBe('organisational_requirement')
        ->and($link->requirement_reference)->toBe('ORG-SEC-TRAIN-001')
        ->and($link->responsible_role)->toBe('Training Manager')
        ->and($link->evidence_required)->toContain('Linked competency record')
        ->and($link->regulatory_source)->toContain('FR-TRN-002');

    $audit = UasAuditEntry::query()->where('action', 'training.compliance_link.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasTrainingCourse::class)
        ->and($audit->auditable_id)->toBe($course->id)
        ->and($audit->requirement_id)->toBe('FR-TRN-002');
});

it('requires competencies and competency records before a compliance link can be captured', function () {
    $user = complianceLinkUser();

    $this->actingAs($user)
        ->post('/training-courses', complianceLinkPayload([
            'code' => 'TRN-COMP-INVALID',
            'competencies' => [],
            'competency_records' => [],
            'compliance_links' => ['ORG-SEC-TRAIN-002'],
        ]))
        ->assertInvalid(['competencies', 'competency_records']);
});

it('links active regulatory requirement records to required competencies and retained training records', function () {
    $user = complianceLinkUser();
    $requirement = regulatoryTrainingRequirement();

    $this->actingAs($user)
        ->post('/training-courses', complianceLinkPayload([
            'code' => 'TRN-REG-LINK-001',
            'title' => 'Regulatory Link Course',
            'competencies' => ['Part 101 operating limits understood'],
            'competency_records' => ['Remote Pilot Regulation File'],
            'compliance_links' => [],
            'regulatory_requirement_ids' => [$requirement->id],
        ]))
        ->assertRedirect();

    $course = UasTrainingCourse::query()->with(['competencies', 'competencyRecords'])->where('code', 'TRN-REG-LINK-001')->firstOrFail();
    $link = UasTrainingComplianceLink::query()->firstOrFail();

    expect($link->training_course_id)->toBe($course->id)
        ->and($link->regulatory_requirement_id)->toBe($requirement->id)
        ->and($link->source_type)->toBe('regulatory_requirement')
        ->and($link->requirement_reference)->toBe('REG-P101-OPS-001')
        ->and($link->responsible_role)->toBe('Chief Remote Pilot')
        ->and($link->applicability)->toBe('Part 101 commercial UAS operations')
        ->and($link->evidence_required)->toBe('Competency record and assessment result')
        ->and($link->validity_period)->toBe('12 months')
        ->and($link->retention_period)->toBe('5 years')
        ->and($link->training_competency_id)->toBe($course->competencies->first()->id)
        ->and($link->training_competency_record_id)->toBe($course->competencyRecords->first()->id);
});

it('exposes compliance links through the training course Inertia pages', function () {
    $this->withoutVite();

    $user = complianceLinkUser();

    $this->actingAs($user)
        ->post('/training-courses', complianceLinkPayload([
            'code' => 'TRN-COMP-VISIBLE',
            'title' => 'Visible Compliance Link Course',
            'competencies' => ['Visible competency'],
            'competency_records' => ['Visible record'],
            'compliance_links' => ['ORG-VISIBLE-001'],
        ]))
        ->assertRedirect();

    $course = UasTrainingCourse::query()->where('code', 'TRN-COMP-VISIBLE')->firstOrFail();

    $this->actingAs($user)
        ->get('/training-courses')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/courses/index')
            ->where('courses.0.compliance_links_count', 1)
        );

    $this->actingAs($user)
        ->get("/training-courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/courses/show')
            ->where('course.compliance_links.0.requirement_reference', 'ORG-VISIBLE-001')
            ->where('course.compliance_links.0.competency_title', 'Visible competency')
            ->where('course.compliance_links.0.competency_record', 'Visible record / planned')
        );
});

it('exposes active regulatory requirements as course creation options', function () {
    $this->withoutVite();

    $user = complianceLinkUser();
    regulatoryTrainingRequirement();

    $this->actingAs($user)
        ->get('/training-courses/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/courses/create')
            ->where('options.regulatory_requirements.0.requirement_id', 'REG-P101-OPS-001')
            ->where('options.regulatory_requirements.0.title', 'Part 101 operating limits competency')
        );
});

function regulatoryTrainingRequirement(array $overrides = []): RegulatoryRequirement
{
    return RegulatoryRequirement::query()->create([
        'requirement_id' => 'REG-P101-OPS-001',
        'regulation_part' => 'Part 101',
        'clause_reference' => '101.05',
        'title' => 'Part 101 operating limits competency',
        'requirement_text' => 'Pilots must understand the operating limitations applicable to the UAS operation.',
        'responsible_party' => 'Chief Remote Pilot',
        'applicability' => 'Part 101 commercial UAS operations',
        'system_control' => 'Training compliance link',
        'evidence_required' => 'Competency record and assessment result',
        'frequency' => 'annual',
        'validity_period' => '12 months',
        'retention_period' => '5 years',
        'effective_date' => '2026-09-09',
        'official_source' => 'SACAA Part 101 reference pending controlled source import',
        'source_version' => 'FRS v1.0',
        'status' => 'active',
        ...$overrides,
    ]);
}

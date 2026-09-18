<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function trainingUser(array $permissions = ['training.view', 'training.create', 'training.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'training_admin_'.str()->random(8),
        'label' => 'Training Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function trainingPayload(array $overrides = []): array
{
    return [
        'code' => 'TRN-ERP-001',
        'title' => 'Emergency Response Procedures',
        'classification' => 'operator_internal_competency',
        'status' => 'active',
        'summary' => 'Operator internal competency course for emergency response changes.',
        'authority_approval_reference' => null,
        'modules' => ['Emergency Response Overview', 'Incident Command'],
        'lessons' => ['Emergency roles', 'Communication escalation'],
        'resources' => ['Operations Manual ERP extract'],
        'assessments' => ['ERP knowledge check'],
        'competencies' => ['Emergency response briefing completed'],
        'competency_records' => ['Remote Pilot A'],
        ...$overrides,
    ];
}

it('requires training permissions for course catalogue routes', function () {
    $this->withoutVite();

    $viewer = trainingUser(['training.view']);

    $this->actingAs($viewer)->get('/training-courses')->assertOk();
    $this->actingAs($viewer)->get('/training-courses/create')->assertForbidden();
    $this->actingAs($viewer)->post('/training-courses', trainingPayload())->assertForbidden();
});

it('creates a training course learning structure with FR-TRN-001 traceability and audit evidence', function () {
    $user = trainingUser();

    $this->actingAs($user)
        ->post('/training-courses', trainingPayload())
        ->assertRedirect();

    $course = UasTrainingCourse::query()->with(['modules.lessons.resources', 'assessments', 'competencies', 'competencyRecords'])->firstOrFail();

    expect($course->code)->toBe('TRN-ERP-001')
        ->and($course->classification)->toBe('operator_internal_competency')
        ->and($course->modules)->toHaveCount(2)
        ->and($course->modules->first()->lessons)->toHaveCount(2)
        ->and($course->modules->first()->lessons->first()->resources)->toHaveCount(1)
        ->and($course->assessments)->toHaveCount(1)
        ->and($course->competencies)->toHaveCount(1)
        ->and($course->competencyRecords)->toHaveCount(1)
        ->and($course->regulatory_source)->toContain('FR-TRN-001')
        ->and($course->created_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'training.course.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasTrainingCourse::class)
        ->and($audit->auditable_id)->toBe($course->id)
        ->and($audit->requirement_id)->toBe('FR-TRN-001');
});

it('prevents regulated training from being represented without an authority approval reference', function () {
    $user = trainingUser();

    $this->actingAs($user)
        ->post('/training-courses', trainingPayload([
            'code' => 'TRN-ATO-001',
            'classification' => 'regulated_ato',
            'authority_approval_reference' => null,
        ]))
        ->assertInvalid(['authority_approval_reference']);
});

it('validates training course identity classification and structure values', function () {
    $user = trainingUser();

    $this->actingAs($user)
        ->post('/training-courses', trainingPayload([
            'code' => '',
            'title' => '',
            'classification' => 'unclassified',
            'status' => 'unknown',
            'modules' => [str_repeat('x', 190)],
        ]))
        ->assertInvalid(['code', 'title', 'classification', 'status', 'modules.0']);
});

it('exposes training course pages through Inertia', function () {
    $this->withoutVite();

    $user = trainingUser();
    $this->actingAs($user)->post('/training-courses', trainingPayload(['code' => 'TRN-VISIBLE-001', 'title' => 'Visible Training Course']))->assertRedirect();
    $course = UasTrainingCourse::query()->where('code', 'TRN-VISIBLE-001')->firstOrFail();

    $this->actingAs($user)
        ->get('/training-courses')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/courses/index')
            ->where('courses.0.code', 'TRN-VISIBLE-001')
        );

    $this->actingAs($user)
        ->get('/training-courses/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/courses/create')
            ->has('options.classifications.operator_internal_competency')
        );

    $this->actingAs($user)
        ->get("/training-courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('training/courses/show')
            ->where('course.code', 'TRN-VISIBLE-001')
            ->where('course.modules.0.lessons.0.resources.0.title', 'Operations Manual ERP extract')
        );
});

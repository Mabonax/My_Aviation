<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

function evidenceVaultUser(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    if ($permissions !== []) {
        $role = UasRole::query()->create([
            'name' => 'evidence-vault-'.uniqid(),
            'label' => 'Evidence Vault Test Role',
            'permissions' => $permissions,
        ]);

        $role->users()->attach($user);
    }

    return $user;
}

function evidenceVaultOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create(array_merge([
        'legal_entity' => 'Evidence Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'EVD-'.str()->upper(str()->random(6)),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations Lead',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'operating_bases' => ['Midrand'],
        'approved_aircraft' => [],
        'approved_pilots' => [],
        'operations_specifications' => ['VLOS'],
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Evidence vault test operator.',
        'responsible_role' => 'Accountable Manager',
    ], $overrides));
}

function evidenceVaultMembership(User $user, UasOperator $operator, string $role = UasOperatorMembership::ROLE_COMPLIANCE_OFFICER): UasOperatorMembership
{
    return UasOperatorMembership::query()->create([
        'uas_operator_id' => $operator->id,
        'user_id' => $user->id,
        'membership_role' => $role,
        'status' => UasOperatorMembership::STATUS_ACTIVE,
        'joined_at' => now(),
    ]);
}

function evidenceVaultAircraft(UasOperator $operator): UasAircraft
{
    $aircraft = UasAircraft::query()->create([
        'registration' => 'ZT-EVD-'.str()->upper(str()->random(4)),
        'manufacturer' => 'DJI',
        'model' => 'Evidence Test',
        'serial_number' => 'SN-EVD-'.str()->upper(str()->random(6)),
        'operational_status' => 'active_serviceable',
    ]);

    $operator->aircraft()->attach($aircraft->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);

    return $aircraft;
}

function evidenceVaultMission(UasOperator $operator, UasAircraft $aircraft): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-EVD-'.str()->upper(str()->random(5)),
        'purpose' => 'Evidence test mission',
        'location' => 'Midrand',
        'operation_category' => 'vlos',
        'uas_operator_id' => $operator->id,
        'uas_aircraft_id' => $aircraft->id,
        'planned_start_at' => now()->addDay(),
        'planned_end_at' => now()->addDay()->addHour(),
        'maximum_altitude_ft' => 200,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'lifecycle_state' => MissionLifecycleState::Draft,
        'release_gate_state' => 'not_evaluated',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Evidence vault mission test.',
        'responsible_role' => 'Flight Operations Manager',
    ]);
}

it('stores governed evidence files with operator scope metadata links and audit proof', function () {
    Storage::fake('local');

    $manager = evidenceVaultUser();
    $operator = evidenceVaultOperator(['legal_entity' => 'Evidence Managed Operator']);
    $aircraft = evidenceVaultAircraft($operator);
    evidenceVaultMembership($manager, $operator);

    $this->actingAs($manager)
        ->post(route('evidence-documents.store'), [
            'file' => UploadedFile::fake()->create('uasla.pdf', 128, 'application/pdf'),
            'title' => 'UASLA approval evidence',
            'category' => 'aircraft_registration',
            'evidenceable_type' => 'aircraft',
            'evidenceable_id' => $aircraft->id,
            'evidence_role' => 'approval',
            'requirement_id' => 'FR-AIR-001',
            'effective_date' => '2026-09-01',
            'expires_at' => now()->addDays(20)->toDateString(),
            'retention_ends_at' => now()->addYears(5)->toDateString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $document = EvidenceDocument::query()->with('links')->firstOrFail();

    expect($document->uas_operator_id)->toBe($operator->id)
        ->and($document->category)->toBe('aircraft_registration')
        ->and($document->checksum_sha256)->toHaveLength(64)
        ->and($document->links)->toHaveCount(1)
        ->and($document->links->first()->evidenceable_type)->toBe(UasAircraft::class)
        ->and($document->links->first()->requirement_id)->toBe('FR-AIR-001')
        ->and(UasAuditEntry::query()->where('action', 'evidence.document_uploaded')->where('auditable_id', $document->id)->exists())->toBeTrue();

    Storage::disk('local')->assertExists($document->path);
});

it('exposes evidence summaries on web and API records while preserving tenant boundaries', function () {
    Storage::fake('local');

    $manager = evidenceVaultUser();
    $viewer = evidenceVaultUser();
    $outsider = evidenceVaultUser();
    $operator = evidenceVaultOperator(['legal_entity' => 'Evidence Scoped Operator']);
    $otherOperator = evidenceVaultOperator(['legal_entity' => 'Evidence Other Operator']);
    $aircraft = evidenceVaultAircraft($operator);
    $mission = evidenceVaultMission($operator, $aircraft);
    evidenceVaultMembership($manager, $operator);
    evidenceVaultMembership($viewer, $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);
    evidenceVaultMembership($outsider, $otherOperator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    Sanctum::actingAs($manager);

    $this->postJson('/api/v1/evidence-documents', [
        'file' => UploadedFile::fake()->create('mission-auth.pdf', 64, 'application/pdf'),
        'title' => 'Mission authorisation',
        'category' => 'mission_authorisation',
        'evidenceable_type' => 'mission',
        'evidenceable_id' => $mission->id,
        'evidence_role' => 'release',
        'requirement_id' => 'FR-MIS-001',
    ])
        ->assertCreated()
        ->assertJsonPath('data.evidence_document.title', 'Mission authorisation')
        ->assertJsonPath('data.evidence_document.links.0.evidenceable_label', $mission->mission_number);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/v1/evidence-documents')
        ->assertOk()
        ->assertJsonPath('data.evidence_documents.0.title', 'Mission authorisation');

    $this->getJson("/api/v1/missions/{$mission->id}")
        ->assertOk()
        ->assertJsonPath('data.mission.evidence.count', 1)
        ->assertJsonPath('data.mission.evidence.documents.0.requirement_id', 'FR-MIS-001');

    $this->actingAs($viewer)
        ->get(route('evidence-documents.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->where('documents.0.title', 'Mission authorisation')
        );

    Sanctum::actingAs($outsider);

    $this->getJson('/api/v1/evidence-documents')
        ->assertOk()
        ->assertJsonMissing(['title' => 'Mission authorisation']);
});

it('blocks non manager operator members from uploading scoped evidence', function () {
    Storage::fake('local');

    $pilot = evidenceVaultUser();
    $operator = evidenceVaultOperator();
    $aircraft = evidenceVaultAircraft($operator);
    evidenceVaultMembership($pilot, $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($pilot)
        ->post(route('evidence-documents.store'), [
            'file' => UploadedFile::fake()->create('blocked.pdf', 8, 'application/pdf'),
            'title' => 'Blocked evidence',
            'category' => 'aircraft_registration',
            'evidenceable_type' => 'aircraft',
            'evidenceable_id' => $aircraft->id,
        ])
        ->assertForbidden();

    expect(EvidenceDocument::query()->count())->toBe(0)
        ->and(EvidenceLink::query()->count())->toBe(0);
});

<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function regulatoryFeeUser(array $permissions = ['regulations.view', 'regulations.create', 'regulations.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'regulatory_fee_manager_'.str()->random(8),
        'label' => 'Regulatory Fee Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function regulatoryFeePayload(array $overrides = []): array
{
    return [
        'regulation_part' => 'Part 101',
        'transaction_code' => 'UASOC_APPLICATION',
        'description' => 'UASOC application fee',
        'amount' => '1250.00',
        'currency' => 'ZAR',
        'effective_from' => '2026-09-11',
        'source' => 'SACAA 2026 fee schedule import pending official source verification',
        'source_version' => 'SACAA-FEE-2026',
        'status' => 'active',
        'verified_at' => '2026-09-11 08:00:00',
        ...$overrides,
    ];
}

it('requires regulatory permissions for fee engine routes', function () {
    $this->withoutVite();

    $viewer = regulatoryFeeUser(['regulations.view']);
    $fee = RegulatoryFee::query()->create(regulatoryFeePayload());

    $this->actingAs($viewer)->get('/regulatory-fees')->assertOk();
    $this->actingAs($viewer)->get('/regulatory-fees/create')->assertForbidden();
    $this->actingAs($viewer)->post('/regulatory-fees', regulatoryFeePayload(['source_version' => 'SACAA-FEE-2026-B']))->assertForbidden();
    $this->actingAs($viewer)->get("/regulatory-fees/{$fee->id}/supersede")->assertForbidden();
});

it('creates a source controlled regulatory fee with FR-FEE-001 audit evidence', function () {
    $user = regulatoryFeeUser();

    $this->actingAs($user)
        ->post('/regulatory-fees', regulatoryFeePayload())
        ->assertRedirect();

    $fee = RegulatoryFee::query()->where('transaction_code', 'UASOC_APPLICATION')->firstOrFail();

    expect($fee->regulation_part)->toBe('Part 101')
        ->and($fee->amount)->toBe('1250.00')
        ->and($fee->currency)->toBe('ZAR')
        ->and($fee->status)->toBe('active')
        ->and($fee->verified_at)->not->toBeNull();

    $audit = UasAuditEntry::query()->where('action', 'regulatory_fee.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(RegulatoryFee::class)
        ->and($audit->auditable_id)->toBe($fee->id)
        ->and($audit->requirement_id)->toBe('FR-FEE-001');
});

it('creates a new tariff version without overwriting the historical fee amount', function () {
    $user = regulatoryFeeUser();
    $previous = RegulatoryFee::query()->create(regulatoryFeePayload([
        'amount' => '950.00',
        'source_version' => 'SACAA-FEE-2025',
        'effective_from' => '2025-04-01',
    ]));

    $this->actingAs($user)
        ->post("/regulatory-fees/{$previous->id}/supersede", regulatoryFeePayload([
            'amount' => '1350.00',
            'source_version' => 'SACAA-FEE-2026',
            'effective_from' => '2026-04-01',
        ]))
        ->assertRedirect();

    $previous->refresh();
    $next = RegulatoryFee::query()->where('source_version', 'SACAA-FEE-2026')->firstOrFail();

    expect($previous->amount)->toBe('950.00')
        ->and($previous->status)->toBe('superseded')
        ->and($previous->effective_to->toDateString())->toBe('2026-03-31')
        ->and($next->previous_fee_id)->toBe($previous->id)
        ->and($next->amount)->toBe('1350.00')
        ->and($next->status)->toBe('active');

    expect(UasAuditEntry::query()->where('action', 'regulatory_fee.superseded')->where('requirement_id', 'FR-FEE-001')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'regulatory_fee.version_created')->where('requirement_id', 'FR-FEE-001')->exists())->toBeTrue();
});

it('validates fee identity source and effective-date fields', function () {
    $user = regulatoryFeeUser();
    RegulatoryFee::query()->create(regulatoryFeePayload(['transaction_code' => 'UASLA_RENEWAL', 'source_version' => 'SACAA-FEE-2026']));

    $this->actingAs($user)
        ->post('/regulatory-fees', regulatoryFeePayload([
            'regulation_part' => '',
            'transaction_code' => 'UASLA_RENEWAL',
            'description' => '',
            'amount' => '-1',
            'currency' => 'RAND',
            'effective_from' => '2026-02-01',
            'effective_to' => '2026-01-01',
            'source' => '',
            'source_version' => 'SACAA-FEE-2026',
            'status' => 'silently_replaced',
        ]))
        ->assertInvalid(['regulation_part', 'description', 'amount', 'currency', 'effective_to', 'source', 'source_version', 'status']);
});

it('exposes regulatory fee engine pages through Inertia', function () {
    $this->withoutVite();

    $user = regulatoryFeeUser();
    $previous = RegulatoryFee::query()->create(regulatoryFeePayload(['source_version' => 'SACAA-FEE-2025', 'effective_from' => '2025-04-01']));
    $next = RegulatoryFee::query()->create(regulatoryFeePayload(['previous_fee_id' => $previous->id, 'source_version' => 'SACAA-FEE-2026', 'effective_from' => '2026-04-01']));
    $previous->update(['status' => 'superseded', 'effective_to' => '2026-03-31']);

    $this->actingAs($user)
        ->get('/regulatory-fees')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/fees/index')
            ->where('fees.0.transaction_code', 'UASOC_APPLICATION')
        );

    $this->actingAs($user)
        ->get("/regulatory-fees/{$previous->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/fees/show')
            ->where('fee.transaction_code', 'UASOC_APPLICATION')
            ->where('fee.superseding_fees.0.source_version', 'SACAA-FEE-2026')
        );

    $this->actingAs($user)
        ->get("/regulatory-fees/{$next->id}/supersede")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/fees/supersede')
            ->where('fee.source_version', 'SACAA-FEE-2026')
        );
});

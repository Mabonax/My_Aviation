<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Compliance\Application\Queries\ComplianceRegisterReport;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function complianceRegisterUser(array $permissions = ['regulations.view']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'compliance_register_viewer_'.str()->random(8),
        'label' => 'Compliance Register Viewer',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

it('requires regulation view permission for the compliance register', function () {
    $this->withoutVite();

    $user = complianceRegisterUser([]);

    $this->actingAs($user)->get('/compliance/register')->assertForbidden();
});

it('calculates compliance domains while keeping critical findings visible', function () {
    ComplianceFinding::query()->create([
        'requirement_id' => 'FR-PIL-003',
        'state' => 'attention_required',
        'severity' => 'critical',
        'summary' => 'RPC revalidation window missed.',
        'recommended_action' => 'Suspend mission assignment until reviewed.',
        'due_at' => now()->addDay(),
    ]);

    ComplianceFinding::query()->create([
        'requirement_id' => 'FR-MIS-001',
        'state' => 'open',
        'severity' => 'warning',
        'summary' => 'Mission risk assessment is incomplete.',
    ]);

    ComplianceFinding::query()->create([
        'requirement_id' => 'FR-SEC-001',
        'state' => 'open',
        'severity' => 'info',
        'summary' => 'Security awareness evidence needs filing.',
    ]);

    $report = app(ComplianceRegisterReport::class)->execute();
    $pilots = collect($report['domains'])->firstWhere('key', 'pilots');
    $operations = collect($report['domains'])->firstWhere('key', 'operations');
    $security = collect($report['domains'])->firstWhere('key', 'security');

    expect($report['overall_score'])->toBe(93)
        ->and($report['critical_findings_count'])->toBe(1)
        ->and($report['open_findings_count'])->toBe(3)
        ->and($report['open_corrective_actions'])->toBe(1)
        ->and($report['critical_findings'])->toHaveCount(1)
        ->and($report['critical_findings'][0]['requirement_id'])->toBe('FR-PIL-003')
        ->and($pilots['score'])->toBe(60)
        ->and($pilots['status'])->toBe('critical')
        ->and($operations['score'])->toBe(85)
        ->and($operations['status'])->toBe('attention')
        ->and($security['score'])->toBe(95);
});

it('exposes the compliance register through Inertia', function () {
    $this->withoutVite();

    $user = complianceRegisterUser();

    ComplianceFinding::query()->create([
        'requirement_id' => 'FR-TRN-002',
        'state' => 'open',
        'severity' => 'warning',
        'summary' => 'Training compliance link evidence is pending.',
    ]);

    $this->actingAs($user)
        ->get('/compliance/register')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('compliance/register')
            ->where('report.open_findings_count', 1)
            ->where('report.domains.7.key', 'training')
            ->where('report.domains.7.open_findings', 1)
            ->where('report.domains.7.status', 'attention')
        );
});

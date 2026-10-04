<?php

namespace App\Domains\Uas\Productisation\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;

class UserExperienceBootstrap
{
    public function execute(User $user, ?UasOperator $operator, bool $platformAuthority = false): array
    {
        $pilot = $user->pilotProfile()->first();
        $activeMemberships = $user->activeOperatorMemberships()
            ->with('operator')
            ->get();

        $membership = $operator
            ? $activeMemberships->firstWhere('uas_operator_id', $operator->id)
            : null;

        $persona = $this->resolvePersona($membership?->membership_role, $pilot !== null, $platformAuthority);
        $workspace = $operator
            ? [
                'type' => 'operator',
                'operator' => [
                    'id' => $operator->id,
                    'legal_entity' => $operator->legal_entity,
                    'trading_name' => $operator->trading_name,
                    'uasoc_number' => $operator->uasoc_number,
                ],
                'membership_role' => $membership?->membership_role,
            ]
            : [
                'type' => 'personal',
                'operator' => null,
                'membership_role' => null,
            ];

        $steps = $this->onboardingSteps($pilot, $activeMemberships->isNotEmpty());
        $completed = collect($steps)->where('complete', true)->count();
        $total = count($steps);

        return [
            'persona' => $persona,
            'workspace' => $workspace,
            'onboarding' => [
                'complete' => $completed === $total,
                'completed_steps' => $completed,
                'total_steps' => $total,
                'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 100,
                'next_action' => collect($steps)->firstWhere('complete', false)['action'] ?? null,
                'steps' => $steps,
            ],
            'readiness' => [
                'state' => $this->readinessState($steps),
                'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 100,
                'blocking_items' => collect($steps)
                    ->where('blocking', true)
                    ->where('complete', false)
                    ->pluck('label')
                    ->values()
                    ->all(),
            ],
            'capabilities' => $this->capabilities($persona, $operator !== null),
            'navigation' => $this->navigation($persona, $operator !== null),
        ];
    }

    private function resolvePersona(?string $membershipRole, bool $hasPilotProfile, bool $platformAuthority): string
    {
        if ($platformAuthority) {
            return 'platform_admin';
        }

        return match ($membershipRole) {
            UasOperatorMembership::ROLE_ACCOUNTABLE_MANAGER,
            UasOperatorMembership::ROLE_OPERATIONS_MANAGER,
            UasOperatorMembership::ROLE_ADMINISTRATOR => 'operator_manager',
            UasOperatorMembership::ROLE_COMPLIANCE_OFFICER,
            UasOperatorMembership::ROLE_SAFETY_OFFICER => 'compliance_officer',
            UasOperatorMembership::ROLE_MAINTENANCE_OFFICER => 'maintenance_officer',
            UasOperatorMembership::ROLE_REMOTE_PILOT => 'operator_pilot',
            default => $hasPilotProfile ? 'personal_pilot' : 'new_user',
        };
    }

    private function onboardingSteps($pilot, bool $hasOperatorMembership): array
    {
        $medical = $pilot?->medical_status?->value ?? null;

        return [
            [
                'key' => 'identity',
                'label' => 'Account identity',
                'complete' => true,
                'blocking' => true,
                'action' => null,
            ],
            [
                'key' => 'pilot_profile',
                'label' => 'Pilot profile',
                'complete' => $pilot !== null,
                'blocking' => true,
                'action' => '/my/pilot/create',
            ],
            [
                'key' => 'pilot_certificate',
                'label' => 'SACAA pilot certificate',
                'complete' => filled($pilot?->sacaa_certificate_number),
                'blocking' => true,
                'action' => '/my/pilot/edit',
            ],
            [
                'key' => 'medical',
                'label' => 'Medical status',
                'complete' => in_array($medical, ['valid', 'not_required'], true),
                'blocking' => true,
                'action' => '/my/compliance',
            ],
            [
                'key' => 'operator_relationship',
                'label' => 'Operator relationship',
                'complete' => $hasOperatorMembership,
                'blocking' => false,
                'action' => '/operators',
            ],
        ];
    }

    private function readinessState(array $steps): string
    {
        $blockingIncomplete = collect($steps)
            ->where('blocking', true)
            ->contains(fn (array $step): bool => ! $step['complete']);

        if ($blockingIncomplete) {
            return 'red';
        }

        $incomplete = collect($steps)->contains(fn (array $step): bool => ! $step['complete']);

        return $incomplete ? 'amber' : 'green';
    }

    private function capabilities(string $persona, bool $operatorMode): array
    {
        $operatorManager = in_array($persona, ['operator_manager', 'platform_admin'], true);
        $compliance = in_array($persona, ['compliance_officer', 'operator_manager', 'platform_admin'], true);
        $maintenance = in_array($persona, ['maintenance_officer', 'operator_manager', 'platform_admin'], true);

        return [
            'personal_pilot' => true,
            'operator_workspace' => $operatorMode,
            'missions' => $operatorMode,
            'aircraft' => $operatorMode,
            'release_mission' => $operatorManager,
            'manage_operator' => $operatorManager,
            'manage_compliance' => $compliance,
            'manage_maintenance' => $maintenance,
            'manage_platform' => $persona === 'platform_admin',
        ];
    }

    private function navigation(string $persona, bool $operatorMode): array
    {
        if (! $operatorMode) {
            return [
                ['key' => 'home', 'label' => 'My readiness', 'path' => '/dashboard'],
                ['key' => 'pilot', 'label' => 'Pilot profile', 'path' => '/my/pilot'],
                ['key' => 'compliance', 'label' => 'My compliance', 'path' => '/my/compliance'],
                ['key' => 'operators', 'label' => 'Operators', 'path' => '/operators'],
            ];
        }

        $items = [
            ['key' => 'home', 'label' => 'Operations', 'path' => '/dashboard'],
            ['key' => 'missions', 'label' => 'Missions', 'path' => '/missions'],
            ['key' => 'aircraft', 'label' => 'Aircraft', 'path' => '/aircraft'],
            ['key' => 'compliance', 'label' => 'Compliance', 'path' => '/compliance/register'],
        ];

        if (in_array($persona, ['maintenance_officer', 'operator_manager', 'platform_admin'], true)) {
            $items[] = ['key' => 'defects', 'label' => 'Technical', 'path' => '/defects'];
        }

        if (in_array($persona, ['operator_manager', 'platform_admin'], true)) {
            $items[] = ['key' => 'operator', 'label' => 'Operator', 'path' => '/operators'];
        }

        return $items;
    }
}

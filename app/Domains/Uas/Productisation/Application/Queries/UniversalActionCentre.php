<?php

namespace App\Domains\Uas\Productisation\Application\Queries;

use App\Domains\Uas\Aircraft\Application\Queries\AircraftReadinessSummary;
use App\Domains\Uas\Missions\Application\Queries\MissionJourneySummary;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Models\User;
use Illuminate\Support\Carbon;

class UniversalActionCentre
{
    public function __construct(
        private readonly AircraftReadinessSummary $aircraftReadiness,
        private readonly MissionJourneySummary $missionJourney,
    ) {}

    public function execute(User $user, ?UasOperator $operator): array
    {
        $items = collect();

        $this->pilotActions($user, $items);

        if ($operator) {
            $this->missionActions($operator, $items);
            $this->aircraftActions($operator, $items);
        }

        $ordered = $items
            ->sortBy(fn (array $item) => [
                $this->priorityRank($item['priority']),
                $item['due_at'] ?? '9999-12-31T23:59:59+00:00',
                $item['title'],
            ])
            ->values();

        return [
            'summary' => [
                'total' => $ordered->count(),
                'critical' => $ordered->where('priority', 'critical')->count(),
                'warning' => $ordered->where('priority', 'warning')->count(),
                'info' => $ordered->where('priority', 'info')->count(),
            ],
            'items' => $ordered->all(),
            'generated_at' => now()->toISOString(),
        ];
    }

    private function pilotActions(User $user, $items): void
    {
        $pilot = $user->pilotProfile()->with('certificates')->first();

        if (! $pilot) {
            $items->push($this->item(
                'pilot.profile.missing',
                'critical',
                'Pilot profile required',
                'Complete your pilot identity before operational readiness can be established.',
                'pilot',
                null,
                '/my/pilot/create',
            ));
            return;
        }

        if (($pilot->medical_status?->value ?? null) === 'expired') {
            $items->push($this->item(
                'pilot.medical.expired',
                'critical',
                'Medical status expired',
                'Your pilot medical status blocks operational readiness.',
                'pilot',
                $pilot->id,
                '/my/compliance',
            ));
        } elseif (($pilot->medical_status?->value ?? null) === 'unverified') {
            $items->push($this->item(
                'pilot.medical.unverified',
                'warning',
                'Medical status requires verification',
                'Verify your medical status before relying on pilot readiness.',
                'pilot',
                $pilot->id,
                '/my/compliance',
            ));
        }

        $latest = $pilot->certificates->sortByDesc('expiry_date')->first();

        if (! $latest) {
            $items->push($this->item(
                'pilot.certificate.missing',
                'critical',
                'Pilot certificate required',
                'No pilot certificate is recorded for your profile.',
                'pilot',
                $pilot->id,
                '/my/compliance',
            ));
            return;
        }

        if ($latest->expiry_date) {
            $days = Carbon::today()->diffInDays($latest->expiry_date, false);

            if ($days < 0) {
                $items->push($this->item(
                    'pilot.certificate.expired',
                    'critical',
                    'Pilot certificate expired',
                    "Certificate {$latest->certificate_number} expired on {$latest->expiry_date->toDateString()}.",
                    'pilot_certificate',
                    $latest->id,
                    '/my/compliance',
                    $latest->expiry_date->toISOString(),
                ));
            } elseif ($days <= 30) {
                $items->push($this->item(
                    'pilot.certificate.expiring',
                    'warning',
                    'Pilot certificate expiring',
                    "Certificate {$latest->certificate_number} expires in {$days} day(s).",
                    'pilot_certificate',
                    $latest->id,
                    '/my/compliance',
                    $latest->expiry_date->toISOString(),
                ));
            }
        }
    }

    private function missionActions(UasOperator $operator, $items): void
    {
        UasMission::query()
            ->where('uas_operator_id', $operator->id)
            ->whereNotIn('lifecycle_state', ['closed', 'cancelled'])
            ->with(['operator', 'pilot', 'aircraft'])
            ->orderBy('planned_start_at')
            ->limit(25)
            ->get()
            ->each(function (UasMission $mission) use ($items): void {
                $journey = $this->missionJourney->execute($mission);
                $next = $journey['next_action'];

                if (! $next) {
                    return;
                }

                $priority = $journey['readiness']['status'] === 'red'
                    ? 'critical'
                    : 'warning';

                $items->push($this->item(
                    "mission.{$mission->id}.{$next['stage']}",
                    $priority,
                    "{$mission->mission_number}: {$next['label']}",
                    $next['summary'],
                    'mission',
                    $mission->id,
                    $next['action_href'] ?: route('missions.show', $mission, false),
                    $mission->planned_start_at?->toISOString(),
                ));
            });
    }

    private function aircraftActions(UasOperator $operator, $items): void
    {
        $operator->aircraft()
            ->wherePivot('status', 'active')
            ->get()
            ->each(function ($aircraft) use ($items): void {
                $summary = $this->aircraftReadiness->execute($aircraft);

                foreach ($summary['checks'] as $check) {
                    if (! in_array($check['status'], ['red', 'amber'], true)) {
                        continue;
                    }

                    $items->push($this->item(
                        "aircraft.{$aircraft->id}.{$check['code']}",
                        $check['status'] === 'red' ? 'critical' : 'warning',
                        "{$aircraft->registration}: {$check['label']}",
                        $check['summary'],
                        'aircraft',
                        $aircraft->id,
                        route('aircraft.show', $aircraft, false),
                    ));
                }
            });
    }

    private function item(
        string $key,
        string $priority,
        string $title,
        string $summary,
        string $entityType,
        ?int $entityId,
        ?string $actionHref,
        ?string $dueAt = null,
    ): array {
        return [
            'key' => $key,
            'priority' => $priority,
            'title' => $title,
            'summary' => $summary,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action_href' => $actionHref,
            'due_at' => $dueAt,
        ];
    }

    private function priorityRank(string $priority): int
    {
        return match ($priority) {
            'critical' => 0,
            'warning' => 1,
            default => 2,
        };
    }
}

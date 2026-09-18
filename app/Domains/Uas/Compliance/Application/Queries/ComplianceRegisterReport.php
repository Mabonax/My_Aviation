<?php

namespace App\Domains\Uas\Compliance\Application\Queries;

use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;

class ComplianceRegisterReport
{
    private const DOMAINS = [
        'organisation' => ['label' => 'Organisation', 'prefixes' => ['FR-OPS', 'FR-REG', 'FR-CMP', 'FR-REC', 'FR-DOC']],
        'pilots' => ['label' => 'Pilots', 'prefixes' => ['FR-PIL', 'FR-LOG']],
        'aircraft' => ['label' => 'Aircraft', 'prefixes' => ['FR-AIR', 'FR-LA']],
        'operations' => ['label' => 'Operations', 'prefixes' => ['FR-MIS', 'FR-CREW', 'FR-CHK', 'FR-TRK', 'FR-GEO']],
        'maintenance' => ['label' => 'Maintenance', 'prefixes' => ['FR-MNT', 'FR-BAT', 'FR-DEF', 'FR-FOL']],
        'safety' => ['label' => 'Safety', 'prefixes' => ['FR-SAF', 'FR-RISK']],
        'security' => ['label' => 'Security', 'prefixes' => ['FR-SEC']],
        'training' => ['label' => 'Training', 'prefixes' => ['FR-TRN']],
    ];

    public function execute(): array
    {
        $findings = ComplianceFinding::query()
            ->whereNull('resolved_at')
            ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")
            ->orderBy('due_at')
            ->get();

        $domains = collect(self::DOMAINS)
            ->map(fn (array $definition, string $key): array => $this->domain($key, $definition, $findings))
            ->values();

        $criticalFindings = $findings->where('severity', 'critical')->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'overall_score' => (int) round($domains->avg('score') ?? 100),
            'critical_findings_count' => $criticalFindings->count(),
            'open_findings_count' => $findings->count(),
            'expiring_within_30_days' => 0,
            'open_corrective_actions' => $findings->whereNotNull('recommended_action')->count(),
            'domains' => $domains->all(),
            'critical_findings' => $criticalFindings->map(fn (ComplianceFinding $finding): array => $this->finding($finding))->all(),
        ];
    }

    private function domain(string $key, array $definition, $findings): array
    {
        $domainFindings = $findings->filter(fn (ComplianceFinding $finding): bool => $this->matches($finding->requirement_id, $definition['prefixes']));
        $critical = $domainFindings->where('severity', 'critical')->count();
        $warnings = $domainFindings->where('severity', 'warning')->count();
        $info = $domainFindings->count() - $critical - $warnings;
        $score = max(0, 100 - ($critical * 40) - ($warnings * 15) - ($info * 5));

        return [
            'key' => $key,
            'label' => $definition['label'],
            'score' => $score,
            'status' => $critical > 0 ? 'critical' : ($domainFindings->isNotEmpty() ? 'attention' : 'clear'),
            'open_findings' => $domainFindings->count(),
            'critical_findings' => $critical,
            'warning_findings' => $warnings,
            'info_findings' => $info,
        ];
    }

    private function matches(?string $requirementId, array $prefixes): bool
    {
        if (! $requirementId) {
            return false;
        }

        return collect($prefixes)->contains(fn (string $prefix): bool => str_starts_with($requirementId, $prefix));
    }

    private function finding(ComplianceFinding $finding): array
    {
        return [
            'id' => $finding->id,
            'requirement_id' => $finding->requirement_id,
            'state' => $finding->state,
            'severity' => $finding->severity,
            'summary' => $finding->summary,
            'recommended_action' => $finding->recommended_action,
            'due_at' => $finding->due_at?->toDateString(),
        ];
    }
}

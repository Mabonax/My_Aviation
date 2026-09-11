<?php

namespace App\Domains\Uas\Pilots\Domain\Services;

use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use Carbon\CarbonInterface;

class PilotComplianceEvaluator
{
    public function rpcState(?PilotCertificate $certificate, ?CarbonInterface $asOf = null): string
    {
        $asOf ??= now();

        if (! $certificate || ! $certificate->expiry_date) {
            return 'unknown_unverified';
        }

        if ($certificate->status === 'suspended') {
            return 'suspended';
        }

        if ($certificate->expiry_date->isPast()) {
            return 'expired';
        }

        if ($certificate->expiry_date->betweenIncluded($asOf, $asOf->copy()->addDays(120))) {
            return 'expiring';
        }

        return 'valid';
    }

    public function revalidationWindow(?PilotCertificate $certificate): ?array
    {
        if (! $certificate?->expiry_date) {
            return null;
        }

        return [
            'opens_at' => $certificate->expiry_date->copy()->subDays(120)->toDateString(),
            'expires_at' => $certificate->expiry_date->toDateString(),
            'alert_points' => [120, 90, 60, 30, 14, 0],
        ];
    }

    public function submissionDeadline(?PilotCertificate $certificate): ?string
    {
        return $certificate?->post_revalidation_submission_due_at?->toDateString();
    }
}

<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Services\CertificateCaseLifecycle;

class CertificateCaseOptions
{
    public function execute(): array
    {
        return [
            'types' => CertificateCaseLifecycle::TYPES,
            'statuses' => CertificateCaseLifecycle::STATUSES,
        ];
    }
}
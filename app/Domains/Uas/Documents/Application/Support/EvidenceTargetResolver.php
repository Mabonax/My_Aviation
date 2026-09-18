<?php

namespace App\Domains\Uas\Documents\Application\Support;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EvidenceTargetResolver
{
    /** @var array<string, class-string<Model>> */
    private const TARGETS = [
        'operator' => UasOperator::class,
        'aircraft' => UasAircraft::class,
        'mission' => UasMission::class,
        'compliance_finding' => ComplianceFinding::class,
    ];

    public function resolve(?string $type, ?int $id): ?Model
    {
        if ($type === null && $id === null) {
            return null;
        }

        if (! isset(self::TARGETS[$type ?? '']) || $id === null) {
            throw ValidationException::withMessages([
                'evidenceable_type' => 'Select a supported evidence target.',
            ]);
        }

        $model = self::TARGETS[$type]::query()->find($id);

        if (! $model) {
            throw ValidationException::withMessages([
                'evidenceable_id' => 'The selected evidence target was not found.',
            ]);
        }

        return $model;
    }

    public function typeFor(Model $model): string
    {
        foreach (self::TARGETS as $type => $class) {
            if ($model instanceof $class) {
                return $type;
            }
        }

        return class_basename($model);
    }
}

<?php

namespace App\Domains\Uas\Pilots\Infrastructure\Repositories;

use App\Domains\Uas\Pilots\Domain\Contracts\PilotRepositoryInterface;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Support\Collection;

class EloquentPilotRepository implements PilotRepositoryInterface
{
    public function latestProfiles(): Collection
    {
        return UasPilot::query()->latest()->get();
    }

    public function create(array $attributes): UasPilot
    {
        return UasPilot::query()->create($attributes);
    }

    public function update(UasPilot $pilot, array $attributes): UasPilot
    {
        $pilot->forceFill($attributes)->save();

        return $pilot->refresh();
    }
}

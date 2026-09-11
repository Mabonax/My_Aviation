<?php

namespace App\Domains\Uas\Pilots\Domain\Contracts;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Support\Collection;

interface PilotRepositoryInterface
{
    /**
     * @return Collection<int, UasPilot>
     */
    public function latestProfiles(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): UasPilot;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UasPilot $pilot, array $attributes): UasPilot;
}

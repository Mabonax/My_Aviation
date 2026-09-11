<?php

namespace App\Domains\Uas\Pilots\Application\Queries;

use App\Domains\Uas\Pilots\Domain\Contracts\PilotRepositoryInterface;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Support\Collection;

class ListPilotProfiles
{
    public function __construct(private readonly PilotRepositoryInterface $pilots) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(): Collection
    {
        return $this->pilots->latestProfiles()
            ->map(fn (UasPilot $pilot): array => PilotProfilePresenter::toArray($pilot));
    }
}

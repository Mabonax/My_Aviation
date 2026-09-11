<?php

namespace App\Providers;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Domain\Policies\MissionPolicy;
use App\Domains\Uas\Pilots\Domain\Contracts\PilotRepositoryInterface;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Pilots\Domain\Policies\PilotProfilePolicy;
use App\Domains\Uas\Pilots\Infrastructure\Repositories\EloquentPilotRepository;
use App\Domains\Uas\Records\Domain\Contracts\AuditEntryRepositoryInterface;
use App\Domains\Uas\Records\Infrastructure\Repositories\EloquentAuditEntryRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UasDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PilotRepositoryInterface::class, EloquentPilotRepository::class);
        $this->app->bind(AuditEntryRepositoryInterface::class, EloquentAuditEntryRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(UasPilot::class, PilotProfilePolicy::class);
        Gate::policy(UasMission::class, MissionPolicy::class);
    }
}

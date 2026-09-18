<?php

namespace App\Providers;

use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Infrastructure\WorkosSdkGateway;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Policies\AircraftPolicy;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Documents\Domain\Policies\EvidenceDocumentPolicy;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Domain\Policies\MissionPolicy;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Policies\GisProjectPolicy;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Notifications\Domain\Policies\ComplianceNotificationPolicy;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Policies\OperatorPolicy;
use App\Domains\Uas\Pilots\Domain\Contracts\PilotRepositoryInterface;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Pilots\Domain\Policies\PilotProfilePolicy;
use App\Domains\Uas\Pilots\Infrastructure\Repositories\EloquentPilotRepository;
use App\Domains\Uas\Records\Domain\Contracts\AuditEntryRepositoryInterface;
use App\Domains\Uas\Records\Infrastructure\Repositories\EloquentAuditEntryRepository;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Regulations\Domain\Policies\RegulatoryExternalIntegrationPolicy;
use App\Domains\Uas\Regulations\Domain\Policies\RegulatoryFeePolicy;
use App\Domains\Uas\Regulations\Domain\Policies\RegulatoryFormPolicy;
use App\Domains\Uas\Regulations\Domain\Policies\RegulatoryRequirementPolicy;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Domains\Uas\Training\Domain\Policies\TrainingCoursePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UasDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface::class, \App\Domains\Uas\AeronauticalInformation\Infrastructure\Repositories\EloquentAeronauticalRepository::class);
        $this->app->bind(WorkosGateway::class, fn () => new WorkosSdkGateway(new \WorkOS\WorkOS(
            apiKey: config('workos.api_key'),
            clientId: config('workos.client_id'),
            timeout: 10,
            maxRetries: 0,
        )));
        $this->app->bind(PilotRepositoryInterface::class, EloquentPilotRepository::class);
        $this->app->bind(AuditEntryRepositoryInterface::class, EloquentAuditEntryRepository::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });

        Gate::policy(\App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem::class, \App\Domains\Uas\AeronauticalInformation\Domain\Policies\AeronauticalInformationPolicy::class);
        Gate::policy(UasPilot::class, PilotProfilePolicy::class);
        Gate::policy(UasOperator::class, OperatorPolicy::class);
        Gate::policy(UasAircraft::class, AircraftPolicy::class);
        Gate::policy(EvidenceDocument::class, EvidenceDocumentPolicy::class);
        Gate::policy(UasMission::class, MissionPolicy::class);
        Gate::policy(UasGisProject::class, GisProjectPolicy::class);
        Gate::policy(UasTrainingCourse::class, TrainingCoursePolicy::class);
        Gate::policy(RegulatoryRequirement::class, RegulatoryRequirementPolicy::class);
        Gate::policy(RegulatoryForm::class, RegulatoryFormPolicy::class);
        Gate::policy(RegulatoryFee::class, RegulatoryFeePolicy::class);
        Gate::policy(RegulatoryExternalIntegration::class, RegulatoryExternalIntegrationPolicy::class);
        Gate::policy(ComplianceNotification::class, ComplianceNotificationPolicy::class);
    }
}

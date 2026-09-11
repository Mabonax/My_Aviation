<?php

namespace App\Domains\Uas\Missions\Domain\Enums;

enum MissionLifecycleState: string
{
    case Draft = 'draft';
    case Planning = 'planning';
    case ComplianceReview = 'compliance_review';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case ReadyForFlight = 'ready_for_flight';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case PostFlightReview = 'post_flight_review';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}

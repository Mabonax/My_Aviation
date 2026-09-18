<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\AcknowledgeManualDistribution;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionPresenter;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualDistribution;
use App\Domains\Uas\Operators\Http\Requests\AcknowledgeManualDistributionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperationsManualAcknowledgementController extends Controller
{
    public function edit(UasOperationsManualDistribution $distribution): Response
    {
        $this->authorizeAcknowledgement($distribution);

        $distribution->loadMissing('manualRevision.operator');

        return Inertia::render('operators/manual-revisions/distributions/acknowledge', [
            'distribution' => [
                'id' => $distribution->id,
                'recipient_name' => $distribution->recipient_name,
                'recipient_role' => $distribution->recipient_role,
                'recipient_email' => $distribution->recipient_email,
                'acknowledgement_status' => $distribution->acknowledgement_status ?? 'pending',
                'acknowledged_at' => $distribution->acknowledged_at?->toDateTimeString(),
                'acknowledgement_statement' => $distribution->acknowledgement_statement,
                'acknowledgement_notes' => $distribution->acknowledgement_notes,
            ],
            'manualRevision' => ManualRevisionPresenter::toArray($distribution->manualRevision),
        ]);
    }

    public function update(AcknowledgeManualDistributionRequest $request, UasOperationsManualDistribution $distribution, AcknowledgeManualDistribution $acknowledge): RedirectResponse
    {
        $acknowledge->execute($distribution, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operations-manual-revisions.show', $distribution->manualRevision)->with('success', 'Manual revision acknowledgement recorded.');
    }

    private function authorizeAcknowledgement(UasOperationsManualDistribution $distribution): void
    {
        $user = request()->user();

        if ($user && $distribution->recipient_email && strcasecmp($distribution->recipient_email, $user->email) === 0) {
            return;
        }

        Gate::authorize('update', $distribution->manualRevision->operator);
    }
}

<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\CreateCertificateCase;
use App\Domains\Uas\Operators\Application\Actions\UpdateCertificateCase;
use App\Domains\Uas\Operators\Application\Queries\CertificateCaseOptions;
use App\Domains\Uas\Operators\Application\Queries\CertificateCasePresenter;
use App\Domains\Uas\Operators\Application\Queries\OperatorPresenter;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Operators\Http\Requests\StoreCertificateCaseRequest;
use App\Domains\Uas\Operators\Http\Requests\UpdateCertificateCaseRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperatorCertificateCaseController extends Controller
{
    public function create(UasOperator $operator, CertificateCaseOptions $options): Response
    {
        Gate::authorize('update', $operator);

        return Inertia::render('operators/certificate-cases/create', [
            'operator' => OperatorPresenter::toArray($operator),
            'options' => $options->execute(),
        ]);
    }

    public function store(StoreCertificateCaseRequest $request, UasOperator $operator, CreateCertificateCase $createCase): RedirectResponse
    {
        $case = $createCase->execute($operator, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operator-certificate-cases.show', $case)->with('success', 'Certificate case created.');
    }

    public function show(UasOperatorCertificateCase $certificateCase): Response
    {
        Gate::authorize('view', $certificateCase->operator);

        return Inertia::render('operators/certificate-cases/show', ['certificateCase' => CertificateCasePresenter::toArray($certificateCase)]);
    }

    public function edit(UasOperatorCertificateCase $certificateCase, CertificateCaseOptions $options): Response
    {
        Gate::authorize('update', $certificateCase->operator);

        return Inertia::render('operators/certificate-cases/edit', [
            'certificateCase' => CertificateCasePresenter::toArray($certificateCase),
            'options' => $options->execute(),
        ]);
    }

    public function update(UpdateCertificateCaseRequest $request, UasOperatorCertificateCase $certificateCase, UpdateCertificateCase $updateCase): RedirectResponse
    {
        $case = $updateCase->execute($certificateCase, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operator-certificate-cases.show', $case)->with('success', 'Certificate case updated.');
    }
}
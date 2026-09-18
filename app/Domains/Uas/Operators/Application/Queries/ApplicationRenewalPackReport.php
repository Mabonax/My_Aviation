<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApplicationRenewalPackReport
{
    public function execute(UasOperatorCertificateCase $case): array
    {
        $case->loadMissing('operator');

        $forms = $this->formsFor($case);
        $fees = $this->feesFor($case);
        $checklist = $this->checklist($case, $forms, $fees);

        return [
            'requirement_id' => 'FR-PACK-001',
            'case' => CertificateCasePresenter::toArray($case),
            'cover_sheet' => [
                'title' => strtoupper(str_replace('_', ' ', $case->case_type)).' PACK',
                'operator' => $case->operator->legal_entity,
                'case_number' => $case->case_number,
                'deadline_at' => $case->deadline_at?->toDateString(),
                'regulatory_source' => $case->regulatory_source,
                'regulatory_source_version' => $case->regulatory_source_version,
            ],
            'required_forms' => $forms->values()->all(),
            'applicable_fees' => $fees->values()->all(),
            'checklist' => $checklist,
            'evidence_index' => $this->evidenceIndex($case),
            'readiness' => $this->readiness($checklist),
        ];
    }

    private function formsFor(UasOperatorCertificateCase $case): Collection
    {
        $terms = $this->transactionTerms($case);

        return RegulatoryForm::query()
            ->where('status', 'active')
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('required_transaction', 'like', '%'.$term.'%');
                }
            })
            ->orderBy('form_code')
            ->get()
            ->map(fn (RegulatoryForm $form): array => [
                'id' => $form->id,
                'form_code' => $form->form_code,
                'form_title' => $form->form_title,
                'revision' => $form->revision,
                'required_transaction' => $form->required_transaction,
                'source_reference' => $form->source_reference,
            ]);
    }

    private function feesFor(UasOperatorCertificateCase $case): Collection
    {
        $codes = $this->feeTransactionCodes($case);

        return RegulatoryFee::query()
            ->where('status', 'active')
            ->whereIn('transaction_code', $codes)
            ->orderBy('transaction_code')
            ->get()
            ->map(fn (RegulatoryFee $fee): array => [
                'id' => $fee->id,
                'transaction_code' => $fee->transaction_code,
                'description' => $fee->description,
                'amount' => $fee->amount,
                'currency' => $fee->currency,
                'source_version' => $fee->source_version,
            ]);
    }

    private function checklist(UasOperatorCertificateCase $case, Collection $forms, Collection $fees): array
    {
        $outstanding = collect($case->outstanding_documents ?? [])->map(fn (string $item): string => Str::lower($item));
        $items = collect($case->evidence_requirements ?? [])->map(fn (string $requirement): array => [
            'label' => $requirement,
            'category' => 'evidence',
            'complete' => ! $outstanding->contains(Str::lower($requirement)),
        ]);

        $items->push([
            'label' => 'Current required forms attached',
            'category' => 'forms',
            'complete' => $forms->isNotEmpty(),
        ]);

        $items->push([
            'label' => 'Applicable regulatory fees identified',
            'category' => 'fees',
            'complete' => $fees->isNotEmpty() || count($case->fees ?? []) > 0,
        ]);

        if ($case->operations_manual_revision) {
            $items->push([
                'label' => 'Operations Manual revision referenced',
                'category' => 'manual',
                'complete' => true,
            ]);
        }

        return $items->values()->all();
    }

    private function evidenceIndex(UasOperatorCertificateCase $case): array
    {
        return [
            ['category' => 'Aircraft', 'items' => $case->fleet_scope ?? []],
            ['category' => 'Personnel', 'items' => $case->personnel_scope ?? []],
            ['category' => 'OpsSpec', 'items' => $case->ops_spec_scope ?? []],
            ['category' => 'Operations Manual', 'items' => array_filter([$case->operations_manual_revision])],
            ['category' => 'Authority Correspondence', 'items' => $case->authority_correspondence ?? []],
            ['category' => 'Outstanding Documents', 'items' => $case->outstanding_documents ?? []],
        ];
    }

    private function readiness(array $checklist): array
    {
        $total = count($checklist);
        $complete = collect($checklist)->where('complete', true)->count();

        return [
            'complete' => $complete,
            'total' => $total,
            'score' => $total === 0 ? 100 : (int) round(($complete / $total) * 100),
        ];
    }

    private function transactionTerms(UasOperatorCertificateCase $case): array
    {
        return match ($case->case_type) {
            'application' => ['application', 'UASOC application', 'ROC application'],
            'amendment' => ['amendment', 'UASOC amendment', 'ROC amendment'],
            'renewal' => ['renewal', 'UASOC renewal', 'UASLA renewal', 'ROC renewal'],
            default => [$case->case_type],
        };
    }

    private function feeTransactionCodes(UasOperatorCertificateCase $case): array
    {
        return match ($case->case_type) {
            'application' => ['UASOC_APPLICATION', 'ROC_APPLICATION'],
            'amendment' => ['UASOC_AMENDMENT', 'ROC_AMENDMENT'],
            'renewal' => ['UASOC_RENEWAL', 'UASLA_RENEWAL', 'ROC_RENEWAL'],
            default => [strtoupper($case->case_type)],
        };
    }
}

<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRegulatoryForm
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryForm
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): RegulatoryForm {
            $form = RegulatoryForm::query()->create([
                ...$this->attributes($data),
                'status' => $data['status'] ?? 'active',
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $form, 'regulatory_form.created', 'FR-FRM-001', $form->source_reference, null, $form->getAttributes(), $ipAddress, $userAgent));

            return $form;
        });
    }

    private function attributes(array $data): array
    {
        return [
            'form_code' => $data['form_code'],
            'form_title' => $data['form_title'],
            'regulatory_area' => $data['regulatory_area'],
            'revision' => $data['revision'],
            'effective_date' => $data['effective_date'],
            'superseded_date' => $data['superseded_date'] ?? null,
            'source_reference' => $data['source_reference'],
            'source_url' => $data['source_url'] ?? null,
            'required_transaction' => $data['required_transaction'],
            'verified_at' => $data['verified_at'] ?? null,
        ];
    }
}

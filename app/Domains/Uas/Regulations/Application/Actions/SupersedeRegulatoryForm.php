<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupersedeRegulatoryForm
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(RegulatoryForm $previous, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryForm
    {
        return DB::transaction(function () use ($previous, $data, $actor, $ipAddress, $userAgent): RegulatoryForm {
            $previousValues = $previous->getAttributes();
            $previous->update([
                'status' => 'superseded',
                'superseded_date' => $data['effective_date'],
            ]);

            $next = RegulatoryForm::query()->create([
                'previous_form_id' => $previous->id,
                'form_code' => $data['form_code'],
                'form_title' => $data['form_title'],
                'regulatory_area' => $data['regulatory_area'],
                'revision' => $data['revision'],
                'effective_date' => $data['effective_date'],
                'source_reference' => $data['source_reference'],
                'source_url' => $data['source_url'] ?? null,
                'required_transaction' => $data['required_transaction'],
                'status' => 'active',
                'verified_at' => $data['verified_at'] ?? null,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $previous, 'regulatory_form.superseded', 'FR-FRM-001', $previous->source_reference, $previousValues, $previous->getAttributes(), $ipAddress, $userAgent));
            $this->recordAuditEntry->execute(new AuditEntryData($actor, $next, 'regulatory_form.version_created', 'FR-FRM-001', $next->source_reference, null, $next->getAttributes(), $ipAddress, $userAgent));

            return $next;
        });
    }
}

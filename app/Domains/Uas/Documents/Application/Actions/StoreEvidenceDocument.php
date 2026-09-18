<?php

namespace App\Domains\Uas\Documents\Application\Actions;

use App\Domains\Uas\Documents\Application\Support\EvidenceOperatorResolver;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreEvidenceDocument
{
    public function __construct(
        private readonly EvidenceOperatorResolver $operatorResolver,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UploadedFile $file, array $data, User $actor, ?Model $evidenceable = null, ?string $ipAddress = null, ?string $userAgent = null): EvidenceDocument
    {
        return DB::transaction(function () use ($file, $data, $actor, $evidenceable, $ipAddress, $userAgent): EvidenceDocument {
            $operatorId = $this->operatorResolver->operatorIdFor($evidenceable, isset($data['uas_operator_id']) ? (int) $data['uas_operator_id'] : null);
            $documentUid = 'EVD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $directory = 'uas-evidence/'.($operatorId ?: 'global').'/'.now()->format('Y/m');
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'evidence';
            $path = "{$directory}/{$documentUid}-{$filename}.{$extension}";

            Storage::disk('local')->putFileAs($directory, $file, basename($path));

            $document = EvidenceDocument::query()->create([
                'document_uid' => $documentUid,
                'uas_operator_id' => $operatorId,
                'uploaded_by_user_id' => $actor->id,
                'title' => $data['title'],
                'category' => $data['category'],
                'status' => $data['status'] ?? 'active',
                'disk' => 'local',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
                'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
                'version' => (int) ($data['version'] ?? 1),
                'access_level' => $data['access_level'] ?? 'operator',
                'source_reference' => $data['source_reference'] ?? null,
                'effective_date' => $data['effective_date'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'retention_ends_at' => $data['retention_ends_at'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-DOC-001/FR-REC-001/FR-REC-002',
                'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
                'regulatory_effective_date' => '2026-09-09',
                'regulatory_applicability' => 'Governed evidence document storage, ownership scoping, metadata, expiry and retention controls.',
            ]);

            if ($evidenceable !== null) {
                $document->links()->create([
                    'evidenceable_type' => $evidenceable::class,
                    'evidenceable_id' => $evidenceable->id,
                    'evidence_role' => $data['evidence_role'] ?? 'supporting',
                    'requirement_id' => $data['requirement_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'attached_by_user_id' => $actor->id,
                    'attached_at' => now(),
                    'metadata' => $data['link_metadata'] ?? [],
                ]);
            }

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $document,
                action: 'evidence.document_uploaded',
                requirementId: 'FR-DOC-001',
                regulatorySource: 'UAS Compliance & Operations Platform FRS FR-DOC-001/FR-REC-001/FR-REC-002',
                previousValues: null,
                newValues: [
                    'document_uid' => $document->document_uid,
                    'uas_operator_id' => $document->uas_operator_id,
                    'title' => $document->title,
                    'category' => $document->category,
                    'path' => $document->path,
                    'checksum_sha256' => $document->checksum_sha256,
                    'linked_to' => $evidenceable ? [$evidenceable::class, $evidenceable->id] : null,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $document->fresh(['links.evidenceable', 'operator', 'uploader']);
        });
    }
}

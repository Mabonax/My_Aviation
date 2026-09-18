<?php

namespace App\Domains\Uas\Documents\Application\Queries;

use App\Domains\Uas\Documents\Application\Support\EvidenceTargetResolver;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;

class EvidenceDocumentPresenter
{
    public static function toArray(EvidenceDocument $document): array
    {
        $document->loadMissing(['links.evidenceable', 'operator', 'uploader']);

        return [
            'id' => $document->id,
            'document_uid' => $document->document_uid,
            'operator' => $document->operator ? [
                'id' => $document->operator->id,
                'legal_entity' => $document->operator->legal_entity,
            ] : null,
            'title' => $document->title,
            'category' => $document->category,
            'status' => self::statusFor($document),
            'stored_status' => $document->status,
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'checksum_sha256' => $document->checksum_sha256,
            'version' => $document->version,
            'access_level' => $document->access_level,
            'source_reference' => $document->source_reference,
            'effective_date' => $document->effective_date?->toDateString(),
            'expires_at' => $document->expires_at?->toISOString(),
            'retention_ends_at' => $document->retention_ends_at?->toISOString(),
            'metadata' => $document->metadata ?? [],
            'uploaded_by' => $document->uploader ? [
                'id' => $document->uploader->id,
                'name' => $document->uploader->name,
            ] : null,
            'links' => $document->links->map(fn (EvidenceLink $link): array => self::linkToArray($link))->values()->all(),
            'created_at' => $document->created_at?->toISOString(),
        ];
    }

    public static function summaryFor(EvidenceDocument $document): array
    {
        return [
            'id' => $document->id,
            'document_uid' => $document->document_uid,
            'title' => $document->title,
            'category' => $document->category,
            'status' => self::statusFor($document),
            'version' => $document->version,
            'expires_at' => $document->expires_at?->toISOString(),
        ];
    }

    private static function linkToArray(EvidenceLink $link): array
    {
        $target = $link->evidenceable;

        return [
            'id' => $link->id,
            'evidenceable_type' => $target ? app(EvidenceTargetResolver::class)->typeFor($target) : null,
            'evidenceable_id' => $target?->id,
            'evidenceable_label' => self::targetLabel($target),
            'evidence_role' => $link->evidence_role,
            'requirement_id' => $link->requirement_id,
            'notes' => $link->notes,
            'attached_at' => $link->attached_at?->toISOString(),
        ];
    }

    private static function targetLabel(mixed $target): ?string
    {
        return match (true) {
            $target === null => null,
            isset($target->legal_entity) => $target->legal_entity,
            isset($target->registration) => $target->registration,
            isset($target->mission_number) => $target->mission_number,
            isset($target->summary) => str($target->summary)->limit(80)->toString(),
            default => class_basename($target).' #'.$target->id,
        };
    }

    private static function statusFor(EvidenceDocument $document): string
    {
        if ($document->status !== 'active') {
            return $document->status;
        }

        if ($document->expires_at && $document->expires_at->isPast()) {
            return 'expired';
        }

        if ($document->expires_at && $document->expires_at->lte(now()->addDays(30))) {
            return 'expiring_soon';
        }

        return 'active';
    }
}

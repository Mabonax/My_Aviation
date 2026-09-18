<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Application\ProviderRegistry;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncAeronauticalInformation
{
    public function __construct(private readonly ProviderRegistry $providers, private readonly AeronauticalRepositoryInterface $repository, private readonly NormalizeAeronauticalInformation $normalize, private readonly AuditAeronauticalEvent $audit, private readonly ValidateProviderDataset $validate) {}

    public function execute(string $key, ProviderRequest $request, ?User $actor = null): ProviderSync
    {
        if (! array_key_exists($key, config('aeronautical.providers', []))) {
            throw ValidationException::withMessages(['provider' => 'Provider is not registered.']);
        }
        $sync = ProviderSync::query()->create(['provider' => $key, 'status' => 'running', 'started_at' => now(), 'source_classification' => 'imported_reference']);
        $this->audit->execute($sync, 'aeronautical.sync.started', ['provider' => $key], $actor);
        try {
            $provider = $this->providers->resolve($key);
            $dataset = $provider->fetch($request);
            [$coverage, $metadata] = $this->validate->execute($dataset, $provider);
            $fingerprint = hash('sha256', json_encode([$dataset->timestamp->utc()->toISOString(), $dataset->mode->value, $coverage, $metadata, $dataset->records], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

            return DB::transaction(function () use ($provider, $dataset, $sync, $actor, $request, $coverage, $metadata, $fingerprint) {
                $this->repository->lockDataset();
                $last = ProviderSync::query()->where('provider', $provider->key())->where('status', 'succeeded')->latest('id')->first();
                $outcome = ProviderSync::query()->where('provider', $provider->key())->whereIn('status', ['succeeded', 'failed'])->latest('id')->first();
                if ($outcome && $outcome->id > $sync->id) {
                    throw ValidationException::withMessages(['dataset' => 'A later synchronization has already completed.']);
                }
                $approval = $this->providers->approvalFingerprint($provider);
                $duplicate = $last && $last->dataset_fingerprint === $fingerprint && $last->approval_fingerprint === $approval;
                if (! $duplicate) {
                    $this->validate->assertOrder($dataset, $metadata, $last, $request);
                }
                $created = 0;
                $updated = 0;
                foreach ($dataset->records as $record) {
                    $normalized = $this->normalize->execute($record, $provider);
                    $stored = $this->repository->storeRevision($normalized['source'], $normalized['item']);
                    if ($stored['created']) {
                        $created++;
                        $this->audit->execute($stored['item'], 'aeronautical.record.imported', ['checksum' => $stored['item']->checksum, 'source_record_id' => $stored['item']->source_record_id], $actor);
                        foreach ($stored['superseded'] as $old) {
                            $updated++;
                            $this->audit->execute($old, 'aeronautical.record.superseded', ['replacement_item_id' => $stored['item']->id], $actor);
                        }
                    }
                }
                $revision = $duplicate && $outcome?->status === 'succeeded' ? $last->completion_revision : $this->repository->advanceDataset();
                $sync->update([
                    'status' => 'succeeded', 'completed_at' => now(), 'dataset_timestamp' => $dataset->timestamp->utc(),
                    'coverage' => $coverage, 'usable_for_release' => $this->providers->operationallyApproved($provider),
                    'source_classification' => $provider->classification()->value, 'records_received' => count($dataset->records),
                    'records_created' => $created, 'records_updated' => $updated, 'completion_revision' => $revision,
                    'dataset_mode' => $dataset->mode->value, 'dataset_fingerprint' => $fingerprint,
                    'approval_fingerprint' => $approval, 'sync_metadata' => $metadata, 'duplicate_of_id' => $duplicate ? $last->id : null,
                ]);
                $this->audit->execute($sync, 'aeronautical.sync.completed', $sync->toArray(), $actor);
                if ($dataset->timestamp->addMinutes(max(1, (int) config('aeronautical.providers.'.$provider->key().'.max_age_minutes', 60)))->lte(now())) {
                    $this->audit->execute($sync, 'aeronautical.dataset.stale', ['provider' => $provider->key()], $actor);
                }
                if (($coverage['complete'] ?? false) !== true) {
                    $this->audit->execute($sync, 'aeronautical.coverage.insufficient', ['provider' => $provider->key(), 'dataset_mode' => $dataset->mode->value], $actor);
                }

                return $sync;
            }, 3);
        } catch (Throwable $error) {
            DB::transaction(function () use ($sync, $actor, $error) {
                $this->repository->lockDataset();
                $superseded = ProviderSync::query()->where('provider', $sync->provider)->whereIn('status', ['succeeded', 'failed'])->where('id', '>', $sync->id)->exists();
                // Never propagate remote exception messages, traces or transport configuration.
                $code = $error instanceof ValidationException ? 'dataset_rejected' : 'provider_unavailable';
                $message = $code === 'dataset_rejected' ? 'Provider dataset rejected by conformance checks. No partial data was accepted.' : 'Provider synchronization failed. Retry or contact the integration administrator.';
                $sync->update(['status' => $superseded ? 'superseded' : 'failed', 'completed_at' => now(), 'error_code' => $code, 'error' => $message]);
                if (! $superseded) {
                    $sync->update(['completion_revision' => $this->repository->advanceDataset()]);
                }
                $this->audit->execute($sync, 'aeronautical.sync.failed', ['provider' => $sync->provider, 'error_code' => $code, 'status' => $sync->status], $actor);
                $this->audit->execute($sync, 'aeronautical.'.$code, ['provider' => $sync->provider], $actor);
            });
            throw ValidationException::withMessages(['provider' => $sync->error]);
        }
    }
}

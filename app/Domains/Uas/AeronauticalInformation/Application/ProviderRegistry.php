<?php

namespace App\Domains\Uas\AeronauticalInformation\Application;

use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use Illuminate\Validation\ValidationException;

class ProviderRegistry
{
    public const APPROVAL_EVIDENCE = ['official_access', 'source_authority', 'coverage_semantics', 'freshness', 'cancellation_replacement', 'failure_detection', 'licence_redistribution', 'operational_use', 'sandbox_conformance', 'production_connectivity'];

    public function resolve(string $key): AeronauticalInformationProviderInterface
    {
        if (! array_key_exists($key, config('aeronautical.providers', []))) {
            throw ValidationException::withMessages(['provider' => 'Provider is not registered.']);
        }
        $adapter = config("aeronautical.providers.{$key}.adapter");
        if (! $adapter || config("aeronautical.providers.{$key}.enabled", true) !== true) {
            throw ValidationException::withMessages(['provider' => 'Provider unavailable: no enabled adapter is configured.']);
        }
        $provider = app($adapter);
        if (! $provider instanceof AeronauticalInformationProviderInterface || $provider->key() !== $key
            || $provider->capabilities()->providerId !== $key || $provider->capabilities()->authority !== $provider->classification()
            || $provider->capabilities()->operational !== $provider->usableForRelease()) {
            throw ValidationException::withMessages(['provider' => 'Provider registration does not match its capability contract.']);
        }

        return $provider;
    }

    public function operationallyApproved(AeronauticalInformationProviderInterface $provider): bool
    {
        $cap = $provider->capabilities();
        $settings = config('aeronautical.providers.'.$provider->key(), []);
        if (($settings['enabled'] ?? true) !== true || ($settings['operational'] ?? false) !== true
            || ($settings['approved'] ?? false) !== true || ($settings['adapter'] ?? null) !== $provider::class
            || ! $cap->operational || ! $provider->usableForRelease()
            || ! in_array($cap->authority->value, ['official_live', 'official_publication'], true)
            || ! $cap->supportsDatasetTimestamp || ! $cap->supportsCancellation || ! $cap->supportsReplacement
            || ! in_array('NOTAM', $cap->informationTypes, true) || ! in_array('bbox', $cap->coverageTypes, true)
            || ! in_array('full_snapshot', $cap->datasetModes, true)) {
            return false;
        }
        foreach (self::APPROVAL_EVIDENCE as $requirement) {
            $reference = $settings['approval_evidence'][$requirement] ?? null;
            if (! is_string($reference) || trim($reference) === '') {
                return false;
            }
        }

        return true;
    }

    public function approvalFingerprint(AeronauticalInformationProviderInterface $provider): string
    {
        // Include approval references in the digest, never in API responses or audit payloads.
        return hash('sha256', json_encode([$provider::class, $provider->capabilities()->toArray(),
            config('aeronautical.providers.'.$provider->key().'.approval_evidence', [])], JSON_THROW_ON_ERROR));
    }
}

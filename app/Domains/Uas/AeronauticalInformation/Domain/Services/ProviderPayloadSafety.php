<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Services;

use Illuminate\Validation\ValidationException;

class ProviderPayloadSafety
{
    public function assertSafe(array $payload): void
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && preg_match('/password|secret|token|authorization|cookie|api[_-]?key|credential/i', $key)) {
                throw ValidationException::withMessages(['provider' => 'Credentials must not be included in source evidence.']);
            }
            if (is_array($value)) {
                $this->assertSafe($value);
            } elseif (is_string($value)) {
                if (preg_match('/\bBearer\s+\S+|-----BEGIN .*PRIVATE KEY-----/i', $value)) {
                    throw ValidationException::withMessages(['provider' => 'Credentials must not be included in source evidence.']);
                }
                // References are public, credential-free URLs; signed/query URLs belong in private transport.
                preg_match_all('~https?://[^\s<>"\x27]+~i', $value, $urls);
                foreach ($urls[0] as $url) {
                    $parts = parse_url($url);
                    if ($parts === false || isset($parts['user'], $parts['pass']) || isset($parts['user']) || isset($parts['query']) || isset($parts['fragment'])) {
                        throw ValidationException::withMessages(['provider' => 'Source URLs must not contain credentials, queries or fragments.']);
                    }
                }
            }
        }
    }
}

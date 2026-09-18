<?php

namespace App\Domains\Uas\Access\Application\Actions;

use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Domain\Models\WorkosMobileSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MobileWorkosAuthentication
{
    public function __construct(private WorkosGateway $gateway, private ResolveWorkosUser $users) {}

    public function begin(array $data): array
    {
        $key = $this->key($data['state']);
        $flow = [
            'challenge' => $data['code_challenge'],
            'redirect_uri' => config('workos.mobile_redirect_uri'),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];
        if (! Cache::add($key, $flow, now()->addMinutes(10))) {
            $this->invalid();
        }

        return [
            'authorization_url' => $this->gateway->mobileAuthorizationUrl($data['state'], $data['code_challenge'], $data['screen_hint'] === 'sign-up'),
            'redirect_uri' => $flow['redirect_uri'],
            'expires_in' => 600,
        ];
    }

    public function exchange(array $data): array
    {
        $key = $this->key($data['state']);
        $flow = Cache::lock($key.':lock', 15)->block(3, function () use ($data, $key) {
            $flow = Cache::get($key);
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $data['code_verifier'], true)), '+/', '-_'), '=');
            if (! is_array($flow) || $flow['expires_at'] < now()->timestamp
                || $flow['redirect_uri'] !== config('workos.mobile_redirect_uri')
                || ! hash_equals($flow['challenge'], $challenge)) {
                $this->invalid();
            }
            Cache::forget($key);
            return $flow;
        });

        $identity = $this->gateway->authenticate($data['code'], $data['code_verifier']);
        return DB::transaction(function () use ($data, $identity) {
            $user = $this->users->handle($identity);
            $token = $user->createToken($data['device_name'] ?? 'yaw-mobile', ['*', 'workos'], now()->addDays(30));
            WorkosMobileSession::create([
                'personal_access_token_id' => $token->accessToken->id,
                'sealed_session' => $identity->sealedSession,
                'session_id' => $identity->sessionId,
            ]);
            if ($user->wasRecentlyCreated) {
                DB::afterCommit(fn () => event(new Registered($user)));
            }

            return [
                'token_type' => 'Bearer',
                'access_token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at->toIso8601String(),
                'user' => $user->only(['id', 'name', 'email', 'role']),
            ];
        });
    }

    private function key(string $state): string
    {
        return 'workos:mobile:'.hash('sha256', $state);
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['workos' => 'This sign-in request is invalid or expired. Please start again.']);
    }
}

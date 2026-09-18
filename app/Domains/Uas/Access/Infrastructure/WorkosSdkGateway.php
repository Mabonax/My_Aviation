<?php

namespace App\Domains\Uas\Access\Infrastructure;

use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Domain\DTOs\WorkosIdentity;
use RuntimeException;
use WorkOS\PKCEHelper;
use WorkOS\Resource\AuthenticateResponse;
use WorkOS\Resource\RadarStandaloneAssessRequestAction;
use WorkOS\Resource\UserManagementAuthenticationProvider;
use WorkOS\SessionManager;
use WorkOS\WorkOS;

final class WorkosSdkGateway implements WorkosGateway
{
    public function __construct(private WorkOS $workos) {}

    public function authorizationUrl(string $state, string $verifier, bool $signUp = false): string
    {
        return $this->workos->userManagement()->getAuthorizationUrl(
            redirectUri: config('workos.redirect_uri'),
            codeChallengeMethod: 'S256',
            codeChallenge: PKCEHelper::generateCodeChallenge($verifier),
            screenHint: $signUp ? RadarStandaloneAssessRequestAction::SignUp : RadarStandaloneAssessRequestAction::SignIn,
            provider: UserManagementAuthenticationProvider::Authkit,
            state: $state,
        );
    }

    public function mobileAuthorizationUrl(string $state, string $challenge, bool $signUp): string
    {
        return $this->workos->userManagement()->getAuthorizationUrl(
            redirectUri: config('workos.mobile_redirect_uri'),
            codeChallengeMethod: 'S256',
            codeChallenge: $challenge,
            screenHint: $signUp ? RadarStandaloneAssessRequestAction::SignUp : RadarStandaloneAssessRequestAction::SignIn,
            provider: UserManagementAuthenticationProvider::Authkit,
            state: $state,
        );
    }

    public function revokeSession(string $sessionId): void
    {
        $this->workos->userManagement()->revokeSession($sessionId);
    }

    public function authenticate(string $code, string $verifier): WorkosIdentity
    {
        return $this->identityFromResponse($this->workos->userManagement()->authenticateWithCode(
            code: $code,
            codeVerifier: $verifier,
        ));
    }

    public function validateSession(string $sealedSession): WorkosIdentity
    {
        $data = SessionManager::unsealData($sealedSession, $this->encryptionKey());
        $result = $this->workos->sessionManager()->authenticate(
            $sealedSession, $this->encryptionKey(), config('workos.client_id'),
        );

        if (! $result['authenticated']) {
            // Seal locally: the application encryption key never leaves this server.
            $identity = $this->identityFromResponse($this->workos->userManagement()->authenticateWithRefreshToken(
                refreshToken: $data['refresh_token'],
            ));

            if ($identity->id !== ($data['user']['id'] ?? null)) {
                throw new RuntimeException('WorkOS session identity changed.');
            }

            return $identity;
        }

        return $this->identity($data['user'], $sealedSession, $result['session_id']);
    }

    public function logoutUrl(string $sessionId): string
    {
        return $this->workos->userManagement()->getLogoutUrl(
            sessionId: $sessionId,
            returnTo: rtrim(config('app.url'), '/').'/',
        );
    }

    private function identityFromResponse(AuthenticateResponse $response): WorkosIdentity
    {
        $sealed = SessionManager::sealSessionFromAuthResponse(
            $response->accessToken, $response->refreshToken, $this->encryptionKey(), $response->user->toArray(),
        );
        $result = $this->workos->sessionManager()->authenticate(
            $sealed, $this->encryptionKey(), config('workos.client_id'),
        );

        if (! $result['authenticated'] || empty($result['session_id'])) {
            throw new RuntimeException('WorkOS returned an invalid session.');
        }

        return $this->identity($response->user->toArray(), $sealed, $result['session_id']);
    }

    private function identity(array $user, string $sealedSession, string $sessionId): WorkosIdentity
    {
        return new WorkosIdentity(
            id: $user['id'],
            email: $user['email'],
            emailVerified: $user['email_verified'] === true,
            name: trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')) ?: $user['email'],
            sealedSession: $sealedSession,
            sessionId: $sessionId,
        );
    }

    private function encryptionKey(): string
    {
        return base64_encode(hash_hkdf('sha256', config('app.key'), 32, 'myaviation-workos-session'));
    }
}

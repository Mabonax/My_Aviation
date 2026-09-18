<?php

use App\Domains\Uas\Access\Infrastructure\WorkosSdkGateway;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use WorkOS\SessionManager;
use WorkOS\WorkOS;

beforeEach(function () {
    config(['workos.client_id' => 'client_test', 'workos.redirect_uri' => 'http://localhost/auth/workos/callback']);
});

function workosTestToken(int $expires, string $subject = 'user_workos'): array
{
    static $key;
    $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
    // XAMPP does not always set OPENSSL_CONF for CLI PHP.
    if (PHP_OS_FAMILY === 'Windows' && file_exists('C:/xampp/apache/conf/openssl.cnf')) {
        $options['config'] = 'C:/xampp/apache/conf/openssl.cnf';
    }
    $key ??= openssl_pkey_new($options);
    $details = openssl_pkey_get_details($key);
    $encode = fn ($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    $input = $encode(json_encode(['alg' => 'RS256', 'kid' => 'test-key'])).'.'
        .$encode(json_encode(['sub' => $subject, 'sid' => 'session_test', 'exp' => $expires]));
    openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256);

    return [$input.'.'.$encode($signature), ['keys' => [[
        'kty' => 'RSA', 'use' => 'sig', 'kid' => 'test-key', 'alg' => 'RS256',
        'n' => $encode($details['rsa']['n']), 'e' => $encode($details['rsa']['e']),
    ]]]];
}

function workosTestResponse(string $token, string $id = 'user_workos'): array
{
    return [
        'access_token' => $token, 'refresh_token' => 'refresh-secret',
        'user' => [
            'object' => 'user', 'id' => $id, 'first_name' => 'Test', 'last_name' => 'Pilot',
            'email' => 'pilot@example.com', 'email_verified' => true, 'profile_picture_url' => null,
            'external_id' => null, 'last_sign_in_at' => null,
            'created_at' => '2026-09-14T00:00:00Z', 'updated_at' => '2026-09-14T00:00:00Z',
        ],
    ];
}

test('the installed SDK builds AuthKit authorization with PKCE and server configuration', function () {
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test'));
    $url = $gateway->authorizationUrl('state', str_repeat('a', 43), true);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect(parse_url($url, PHP_URL_HOST))->toBe('api.workos.com')
        ->and($query)->toMatchArray([
            'provider' => 'authkit', 'client_id' => 'client_test', 'state' => 'state',
            'screen_hint' => 'sign-up', 'code_challenge_method' => 'S256',
            'redirect_uri' => 'http://localhost/auth/workos/callback',
        ])->not->toHaveKeys(['client_secret', 'code_verifier']);
    expect($query['code_challenge'])->toBe(rtrim(strtr(base64_encode(hash('sha256', str_repeat('a', 43), true)), '+/', '-_'), '='));
    parse_str(parse_url($gateway->logoutUrl('session_test'), PHP_URL_QUERY), $logout);
    expect($logout)->toMatchArray(['session_id' => 'session_test', 'return_to' => rtrim(config('app.url'), '/').'/']);
});

test('the real SDK exchanges the code verifies the JWT and encrypts tokens locally', function () {
    [$token, $jwks] = workosTestToken(time() + 300);
    $history = [];
    $handler = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(workosTestResponse($token))),
        new Response(200, [], json_encode($jwks)),
    ]));
    $handler->push(Middleware::history($history));
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test', handler: $handler, maxRetries: 0));
    $identity = $gateway->authenticate('auth-code', 'pkce-verifier');
    expect($identity->id)->toBe('user_workos')->and($identity->name)->toBe('Test Pilot')
        ->and($identity->emailVerified)->toBeTrue()->and($identity->sessionId)->toBe('session_test')
        ->and($identity->sealedSession)->not->toContain('refresh-secret')->not->toContain($token);

    $body = json_decode((string) $history[0]['request']->getBody(), true);
    expect($body)->toMatchArray([
        'grant_type' => 'authorization_code', 'code' => 'auth-code', 'code_verifier' => 'pkce-verifier',
        'client_id' => 'client_test', 'client_secret' => 'secret',
    ])->not->toHaveKey('session');
    expect($gateway->validateSession($identity->sealedSession)->id)->toBe('user_workos');
    expect($history)->toHaveCount(2);
});

test('expired sessions refresh through the SDK and remain encrypted', function () {
    [$expiredToken] = workosTestToken(time() - 60);
    [$freshToken, $newJwks] = workosTestToken(time() + 300);
    $key = base64_encode(hash_hkdf('sha256', config('app.key'), 32, 'myaviation-workos-session'));
    $old = SessionManager::sealSessionFromAuthResponse(
        $expiredToken, 'old-refresh', $key, workosTestResponse($expiredToken)['user'],
    );
    $history = [];
    $handler = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode($newJwks)),
        new Response(200, [], json_encode(workosTestResponse($freshToken))),
    ]));
    $handler->push(Middleware::history($history));
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test', handler: $handler, maxRetries: 0));
    $identity = $gateway->validateSession($old);
    expect($identity->id)->toBe('user_workos')->and($identity->sealedSession)->not->toBe($old);
    $body = json_decode((string) $history[1]['request']->getBody(), true);
    expect($body)->toMatchArray(['grant_type' => 'refresh_token', 'refresh_token' => 'old-refresh'])
        ->not->toHaveKey('session');
});

test('invalid provider tokens cannot establish a session', function () {
    $handler = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(workosTestResponse('invalid-jwt')))]));
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test', handler: $handler, maxRetries: 0));
    expect(fn () => $gateway->authenticate('code', 'verifier'))->toThrow(RuntimeException::class, 'invalid session');
});

test('corrupted session ciphertext is rejected before any provider request', function () {
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test', handler: HandlerStack::create(new MockHandler([]))));
    expect(fn () => $gateway->validateSession('corrupted'))->toThrow(InvalidArgumentException::class);
});

test('native authorization uses the registered callback and supplied PKCE challenge', function () {
    config(['workos.mobile_redirect_uri' => 'za.co.vmt.yaw://auth/callback']);
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test'));
    parse_str(parse_url($gateway->mobileAuthorizationUrl('state', 'challenge', true), PHP_URL_QUERY), $query);
    expect($query)->toMatchArray([
        'redirect_uri' => 'za.co.vmt.yaw://auth/callback', 'state' => 'state',
        'code_challenge' => 'challenge', 'code_challenge_method' => 'S256',
        'screen_hint' => 'sign-up', 'provider' => 'authkit',
    ])->not->toHaveKeys(['client_secret', 'code_verifier']);
});

test('native logout revokes the exact provider session through the SDK', function () {
    $history = [];
    $handler = HandlerStack::create(new MockHandler([new Response(204)]));
    $handler->push(Middleware::history($history));
    $gateway = new WorkosSdkGateway(new WorkOS(apiKey: 'secret', clientId: 'client_test', handler: $handler, maxRetries: 0));
    $gateway->revokeSession('session_mobile');
    expect($history)->toHaveCount(1);
    expect($history[0]['request']->getMethod())->toBe('POST');
    expect($history[0]['request']->getUri()->getPath())->toBe('/user_management/sessions/revoke');
    expect(json_decode((string) $history[0]['request']->getBody(), true))->toMatchArray(['session_id' => 'session_mobile']);
});

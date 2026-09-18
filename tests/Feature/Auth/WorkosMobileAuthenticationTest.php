<?php

use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Domain\DTOs\WorkosIdentity;
use App\Domains\Uas\Access\Domain\Models\WorkosMobileSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config([
        'workos.enabled' => true, 'workos.api_key' => 'fake', 'workos.client_id' => 'client_test',
        'workos.redirect_uri' => 'http://localhost/auth/workos/callback',
        'workos.mobile_redirect_uri' => 'za.co.vmt.yaw://auth/callback',
    ]);
    $this->gateway = Mockery::mock(WorkosGateway::class);
    $this->app->instance(WorkosGateway::class, $this->gateway);
    $this->identity = new WorkosIdentity('workos_mobile', 'mobile@example.com', true, 'Mobile Pilot', 'sealed-secret', 'session_mobile');
    $this->state = str_repeat('s', 43);
    $this->verifier = str_repeat('v', 43);
    $this->challenge = rtrim(strtr(base64_encode(hash('sha256', $this->verifier, true)), '+/', '-_'), '=');
    $this->begin = fn (string $hint = 'sign-in') => $this->postJson('/api/v1/auth/workos/authorize', [
        'state' => $this->state, 'code_challenge' => $this->challenge, 'screen_hint' => $hint,
    ]);
    $this->exchange = fn (array $changes = []) => $this->postJson('/api/v1/auth/workos/exchange', array_merge([
        'state' => $this->state, 'code_verifier' => $this->verifier, 'code' => 'provider-code',
    ], $changes));
});

test('mobile login and signup issue state-bound PKCE redirects', function (string $hint) {
    $this->gateway->shouldReceive('mobileAuthorizationUrl')->once()
        ->with($this->state, $this->challenge, $hint === 'sign-up')
        ->andReturn('https://api.workos.com/user_management/authorize');
    ($this->begin)($hint)->assertOk()->assertJsonPath('data.redirect_uri', 'za.co.vmt.yaw://auth/callback')
        ->assertJsonPath('data.expires_in', 600)->assertJsonMissingPath('data.api_key');
    ($this->begin)($hint)->assertStatus(422);
})->with(['sign-in', 'sign-up']);

test('mobile exchange creates a shared account and encrypted bounded Sanctum session', function () {
    $this->gateway->shouldReceive('mobileAuthorizationUrl')->once()->andReturn('https://api.workos.com/user_management/authorize');
    ($this->begin)('sign-up')->assertOk();
    $this->gateway->shouldReceive('authenticate')->once()->with('provider-code', $this->verifier)->andReturn($this->identity);
    $result = ($this->exchange)()->assertCreated()->assertJsonPath('data.user.role', 'user')
        ->assertJsonPath('data.token_type', 'Bearer')->assertJsonMissingPath('data.refresh_token');
    $user = User::where('workos_id', 'workos_mobile')->sole();
    $session = WorkosMobileSession::sole();
    expect($session->sealed_session)->toBe('sealed-secret')
        ->and(DB::table('workos_mobile_sessions')->value('sealed_session'))->not->toContain('sealed-secret')
        ->and($user->tokens()->sole()->expires_at)->not->toBeNull()
        ->and($session->toArray())->not->toHaveKeys(['sealed_session', 'session_id']);
    ($this->exchange)()->assertStatus(422);
    $this->gateway->shouldReceive('validateSession')->once()->with('sealed-secret')->andReturn($this->identity);
    $this->withToken($result->json('data.access_token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.user.id', $user->id);
});

test('mobile exchange rejects invalid verifier unknown state and expired flow', function (string $failure) {
    if ($failure !== 'unknown') {
        $this->gateway->shouldReceive('mobileAuthorizationUrl')->once()->andReturn('https://api.workos.com/authorize');
        ($this->begin)()->assertOk();
    }
    if ($failure === 'expired') {
        $this->travel(11)->minutes();
    }
    ($this->exchange)($failure === 'verifier' ? ['code_verifier' => str_repeat('x', 43)] : [])->assertStatus(422);
    $this->gateway->shouldNotHaveReceived('authenticate');
    $this->assertDatabaseCount('personal_access_tokens', 0);
})->with(['unknown', 'verifier', 'expired']);

test('mobile email collisions and unverified identities cannot claim accounts', function (bool $verified) {
    User::factory()->create(['email' => 'mobile@example.com', 'role' => 'super_admin']);
    $this->gateway->shouldReceive('mobileAuthorizationUrl')->once()->andReturn('https://api.workos.com/authorize');
    ($this->begin)()->assertOk();
    $identity = new WorkosIdentity('workos_mobile', 'mobile@example.com', $verified, 'Mobile', 'sealed', 'sid');
    $this->gateway->shouldReceive('authenticate')->once()->andReturn($identity);
    ($this->exchange)()->assertStatus(422);
    $this->assertDatabaseCount('personal_access_tokens', 0);
    expect(User::sole()->workos_id)->toBeNull();
})->with([true, false]);

test('mobile disabled and provider failure return safe API envelopes', function () {
    config(['workos.enabled' => false]);
    ($this->begin)()->assertStatus(503)->assertJsonPath('error', 'workos_unavailable');
    config(['workos.enabled' => true]);
    $this->gateway->shouldReceive('mobileAuthorizationUrl')->once()->andThrow(new RuntimeException('private-provider-secret'));
    ($this->begin)()->assertStatus(503)->assertDontSee('private-provider-secret');
});

test('mobile WorkOS session failure revokes local access instead of falling back to password auth', function (string $failure) {
    $user = User::factory()->create(['workos_id' => 'workos_mobile']);
    $token = $user->createToken('mobile', ['*', 'workos']);
    if ($failure !== 'missing') {
        WorkosMobileSession::create([
            'personal_access_token_id' => $token->accessToken->id, 'sealed_session' => 'sealed', 'session_id' => 'sid',
        ]);
    }
    if ($failure === 'revoked') {
        $this->gateway->shouldReceive('validateSession')->once()->andThrow(new RuntimeException('revoked'));
    } elseif ($failure === 'mismatch') {
        $this->gateway->shouldReceive('validateSession')->once()->andReturn(new WorkosIdentity('someone_else', 'mobile@example.com', true, 'Other', 'sealed', 'sid'));
    } elseif ($failure === 'disabled') {
        config(['workos.enabled' => false]);
    }
    $this->withToken($token->plainTextToken)->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error', 'unauthenticated');
    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->assertDatabaseCount('workos_mobile_sessions', 0);
})->with(['missing', 'revoked', 'mismatch', 'disabled']);

test('mobile logout revokes only its WorkOS session and local token even on provider failure', function (bool $providerFailure) {
    $user = User::factory()->create(['workos_id' => 'workos_mobile']);
    $token = $user->createToken('workos', ['*', 'workos']);
    $user->createToken('another-device');
    WorkosMobileSession::create(['personal_access_token_id' => $token->accessToken->id, 'sealed_session' => 'sealed', 'session_id' => 'sid']);
    $expectation = $this->gateway->shouldReceive('revokeSession')->once()->with('sid');
    $providerFailure ? $expectation->andThrow(new RuntimeException('unavailable')) : $expectation->andReturnNull();
    $this->withToken($token->plainTextToken)->postJson('/api/v1/auth/logout')->assertOk();
    $this->gateway->shouldNotHaveReceived('validateSession');
    $this->assertDatabaseCount('personal_access_tokens', 1);
    $this->assertDatabaseCount('workos_mobile_sessions', 0);
})->with([false, true]);

test('configured web registration cannot create local password accounts', function () {
    $this->post('/register', ['name' => 'Test', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertRedirect('/auth/workos/register');
    $this->assertDatabaseCount('users', 0);
});

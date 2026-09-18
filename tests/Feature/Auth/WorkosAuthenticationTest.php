<?php

use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Domain\DTOs\WorkosIdentity;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'workos.enabled' => true,
        'workos.api_key' => 'test-api-key',
        'workos.client_id' => 'client_test',
        'workos.redirect_uri' => 'http://localhost/auth/workos/callback',
    ]);
    $this->gateway = Mockery::mock(WorkosGateway::class);
    $this->app->instance(WorkosGateway::class, $this->gateway);
    $this->identity = new WorkosIdentity('user_workos', 'pilot@example.com', true, 'Test Pilot', 'sealed-session', 'session_test');
    $this->flow = [
        'state' => 'one-time-state',
        'verifier' => 'test-verifier',
        'expires_at' => now()->addMinutes(10)->timestamp,
        'user_id' => null,
    ];
});

test('WorkOS is hidden until enabled and configured without exposing secrets', function () {
    config(['workos.api_key' => null]);
    $this->get('/login')->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')->where('workosEnabled', false)->missing('workos.api_key'));
    $this->get('/auth/workos')->assertRedirect('/login')->assertSessionHas('workos_error');
    $this->gateway->shouldNotHaveReceived('authorizationUrl');
});

test('login and registration show WorkOS when configured', function () {
    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('workosEnabled', true));
    $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('workosEnabled', true));
});

test('WorkOS starts a fresh PKCE flow through a full browser redirect', function (string $path, bool $signUp) {
    $this->gateway->shouldReceive('authorizationUrl')->once()
        ->withArgs(fn ($state, $verifier, $signup) => strlen($state) === 64 && strlen($verifier) >= 43 && $signup === $signUp)
        ->andReturn('https://api.workos.com/user_management/authorize?provider=authkit');
    $this->get($path, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create($path)),
    ])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://api.workos.com/user_management/authorize?provider=authkit')
        ->assertSessionHas('workos_oauth', fn ($flow) => $flow['expires_at'] > now()->timestamp && $flow['user_id'] === null);
})->with([['/auth/workos', false], ['/auth/workos/register', true]]);

test('invalid or missing callback state never exchanges a code', function (array $query, ?array $flow) {
    $this->withSession($flow ? ['workos_oauth' => $flow] : [])
        ->get('/auth/workos/callback?'.http_build_query($query))
        ->assertRedirect('/login')->assertSessionHas('workos_error')->assertSessionMissing('workos_oauth');
    $this->assertGuest();
    $this->gateway->shouldNotHaveReceived('authenticate');
})->with([
    'absent session and state' => [['code' => 'code'], null],
    'absent session' => [['state' => 'untrusted', 'code' => 'code'], null],
    'mismatch' => [['state' => 'wrong', 'code' => 'code'], ['state' => 'right', 'expires_at' => PHP_INT_MAX, 'user_id' => null]],
    'array state' => [['state' => ['nested'], 'code' => 'code'], ['state' => 'right', 'expires_at' => PHP_INT_MAX, 'user_id' => null]],
    'expired' => [['state' => 'right', 'code' => 'code'], ['state' => 'right', 'expires_at' => 1, 'user_id' => null]],
]);

test('cancelled and malformed callbacks give a safe retry message', function (array $query) {
    $this->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?'.http_build_query(['state' => $this->flow['state'], ...$query]))
        ->assertRedirect('/login')->assertSessionHas('workos_error')->assertSessionMissing('workos_oauth');
    $this->gateway->shouldNotHaveReceived('authenticate');
})->with([[[]], [['error' => 'access_denied', 'error_description' => '<script>secret</script>']], [['code' => ['invalid']]]]);

test('new verified WorkOS users get a standard account and intended destination', function () {
    $this->gateway->shouldReceive('authenticate')->once()->with('valid-code', 'test-verifier')->andReturn($this->identity);
    $this->withSession(['workos_oauth' => $this->flow, 'url.intended' => '/my/compliance'])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')
        ->assertRedirect('/my/compliance')->assertSessionHas('workos_authenticated', true)
        ->assertSessionHas('workos_session', 'sealed-session')->assertSessionMissing('workos_oauth');

    $user = User::where('workos_id', 'user_workos')->sole();
    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe('user')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeFalse()
        ->and($user->uasRoles()->count())->toBe(0)
        ->and($user->operatorMemberships()->count())->toBe(0)
        ->and($user->toArray())->not->toHaveKey('workos_id');
});

test('unverified WorkOS users cannot create or access accounts', function () {
    $unverified = new WorkosIdentity('user_workos', 'pilot@example.com', false, 'Pilot', 'sealed', 'sid');
    $this->gateway->shouldReceive('authenticate')->once()->andReturn($unverified);
    $this->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')->assertSessionHas('workos_error');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('email collisions require authenticated linking and do not take over local accounts', function () {
    $user = User::factory()->create(['email' => 'Pilot@Example.com', 'role' => 'super_admin']);
    $this->gateway->shouldReceive('authenticate')->once()->andReturn($this->identity);
    $this->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')->assertSessionHas('workos_error');
    $this->assertGuest();
    expect($user->fresh()->workos_id)->toBeNull()->and($user->fresh()->role)->toBe('super_admin');
    $this->assertDatabaseCount('users', 1);
});

test('returning users retain their password role and local profile', function () {
    $user = User::factory()->create(['workos_id' => 'user_workos', 'name' => 'Local Name', 'role' => 'super_admin']);
    $password = $user->password;
    $this->gateway->shouldReceive('authenticate')->once()->andReturn($this->identity);
    $this->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->name)->toBe('Local Name')->and($user->fresh()->role)->toBe('super_admin')
        ->and($user->fresh()->password)->toBe($password);
    $this->assertDatabaseCount('users', 1);
});

test('linking requires authentication and recent password confirmation', function () {
    $this->get('/auth/workos/link')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->get('/auth/workos/link')->assertRedirect('/confirm-password');
    $this->gateway->shouldNotHaveReceived('authorizationUrl');
});

test('confirmed account linking preserves the existing account', function () {
    $user = User::factory()->create(['email' => 'pilot@example.com', 'role' => 'super_admin']);
    $this->gateway->shouldReceive('authorizationUrl')->once()->andReturn('https://api.workos.com/authorize');
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->get('/auth/workos/link')->assertRedirect('https://api.workos.com/authorize')
        ->assertSessionHas('workos_oauth', fn ($flow) => $flow['user_id'] === $user->id);
    $state = session('workos_oauth.state');
    $this->gateway->shouldReceive('authenticate')->once()->andReturn($this->identity);
    $this->get('/auth/workos/callback?state='.$state.'&code=valid-code')
        ->assertRedirect('/settings/profile')->assertSessionHas('status', 'WorkOS connected successfully.');
    expect($user->fresh()->workos_id)->toBe('user_workos')->and($user->fresh()->role)->toBe('super_admin');
    $this->assertDatabaseCount('users', 1);
});

test('linking rejects a different email an occupied identity and an existing different link', function (string $conflict) {
    $user = User::factory()->create([
        'email' => $conflict === 'email' ? 'other@example.com' : 'pilot@example.com',
        'workos_id' => $conflict === 'linked' ? 'user_other' : null,
    ]);
    if ($conflict === 'occupied') {
        User::factory()->create(['workos_id' => 'user_workos']);
    }
    $this->flow['user_id'] = $user->id;
    $this->gateway->shouldReceive('authenticate')->once()->andReturn($this->identity);
    $this->actingAs($user)->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')
        ->assertRedirect('/settings/profile')->assertSessionHas('workos_error');
    expect($user->fresh()->workos_id)->toBe($conflict === 'linked' ? 'user_other' : null);
})->with(['email', 'occupied', 'linked']);

test('a linking callback cannot switch the app account', function () {
    $this->flow['user_id'] = User::factory()->create()->id;
    $this->actingAs(User::factory()->create())->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')->assertSessionHas('workos_error');
    $this->gateway->shouldNotHaveReceived('authenticate');
});

test('provider failures do not expose provider responses and consume the flow', function () {
    $this->gateway->shouldReceive('authenticate')->once()->andThrow(new RuntimeException('secret-api-key-provider-body'));
    $this->withSession(['workos_oauth' => $this->flow])
        ->get('/auth/workos/callback?state=one-time-state&code=valid-code')
        ->assertRedirect('/login')->assertSessionMissing('workos_oauth')
        ->assertSessionHas('workos_error', fn ($error) => ! str_contains($error, 'secret'));
    $this->get('/auth/workos/callback?state=one-time-state&code=valid-code')->assertSessionHas('workos_error');
    $this->assertGuest();
});

test('a WorkOS session is validated and refreshed before accessing protected pages', function () {
    $user = User::factory()->create(['workos_id' => 'user_workos']);
    $this->gateway->shouldReceive('validateSession')->once()->with('old-sealed-session')->andReturn($this->identity);
    $this->actingAs($user)->withSession(['workos_authenticated' => true, 'workos_session' => 'old-sealed-session'])
        ->get('/settings/profile')->assertOk()->assertSessionHas('workos_session', 'sealed-session');
});

test('invalid expired disabled or mismatched WorkOS sessions are logged out', function (string $mode) {
    $user = User::factory()->create(['workos_id' => $mode === 'mismatch' ? 'other' : 'user_workos']);
    if ($mode === 'disabled') {
        config(['workos.enabled' => false]);
    } elseif ($mode === 'invalid') {
        $this->gateway->shouldReceive('validateSession')->once()->andThrow(new RuntimeException('expired'));
    } elseif ($mode === 'mismatch') {
        $this->gateway->shouldReceive('validateSession')->once()->andReturn($this->identity);
    }
    $this->actingAs($user)->withSession([
        'workos_authenticated' => true,
        'workos_session' => $mode === 'missing' ? null : 'sealed-session',
    ])->get('/settings/profile')->assertRedirect('/login')->assertSessionMissing('workos_session');
    $this->assertGuest();
})->with(['invalid', 'missing', 'disabled', 'mismatch']);

test('WorkOS logout clears local session and uses an Inertia external redirect', function () {
    $this->gateway->shouldReceive('logoutUrl')->once()->with('session_test')
        ->andReturn('https://api.workos.com/user_management/sessions/logout?session_id=session_test');
    $this->actingAs(User::factory()->create())->withSession([
        'workos_authenticated' => true, 'workos_session_id' => 'session_test',
    ])->post('/logout', [], ['X-Inertia' => 'true'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://api.workos.com/user_management/sessions/logout?session_id=session_test')
        ->assertSessionMissing('workos_authenticated');
    $this->assertGuest();
    $this->gateway->shouldNotHaveReceived('validateSession');
});

test('provider configuration failure never blocks local logout', function () {
    $this->gateway->shouldReceive('logoutUrl')->andThrow(new RuntimeException('unconfigured'));
    $this->actingAs(User::factory()->create())->withSession([
        'workos_authenticated' => true, 'workos_session_id' => 'session_test',
    ])->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

test('local password sessions remain independent of WorkOS even for linked users', function () {
    $user = User::factory()->create(['workos_id' => 'user_workos']);
    $this->withSession(['workos_authenticated' => true, 'workos_session' => 'stale'])
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/dashboard')->assertSessionMissing('workos_authenticated');
    $this->get('/settings/profile')->assertOk();
    $this->gateway->shouldNotHaveReceived('validateSession');
});

test('linked users cannot change their app email through profile updates', function () {
    $user = User::factory()->create(['workos_id' => 'user_workos']);
    $this->actingAs($user)->patch('/settings/profile', ['name' => $user->name, 'email' => 'changed@example.com'])
        ->assertSessionHasErrors('email');
    expect($user->fresh()->email)->toBe($user->email);
});

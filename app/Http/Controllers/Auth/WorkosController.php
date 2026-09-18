<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Uas\Access\Application\Actions\ResolveWorkosUser;
use App\Domains\Uas\Access\Application\WorkosConfiguration;
use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use WorkOS\PKCEHelper;

class WorkosController extends Controller
{
    public function redirect(Request $request): Response
    {
        if (! WorkosConfiguration::enabled()) {
            return to_route('login')->with('workos_error', 'WorkOS login is not configured yet. Please use email and password.');
        }

        $flow = [
            'state' => Str::random(64),
            'verifier' => PKCEHelper::generateCodeVerifier(),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'user_id' => $request->routeIs('workos.link') ? $request->user()->id : null,
        ];

        try {
            $url = app(WorkosGateway::class)->authorizationUrl($flow['state'], $flow['verifier'], $request->routeIs('workos.register'));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }

        $request->session()->put('workos_oauth', $flow);

        return Inertia::location($url);
    }

    public function callback(Request $request, ResolveWorkosUser $resolveUser): Response
    {
        $flow = $request->session()->pull('workos_oauth');
        $state = $request->query('state');

        if (! is_array($flow) || ! is_string($state) || $state === ''
            || ! hash_equals($flow['state'], $state) || $flow['expires_at'] < now()->timestamp) {
            return to_route('login')->with('workos_error', 'Your WorkOS login request expired or is invalid. Please try again.');
        }

        $linkTo = $flow['user_id'] ? $request->user() : null;
        if (($flow['user_id'] && (! $linkTo || $linkTo->id !== $flow['user_id']))
            || (! $flow['user_id'] && $request->user())) {
            return to_route('login')->with('workos_error', 'Your app session changed. Please start WorkOS login again.');
        }

        $destination = $linkTo ? 'profile.edit' : 'login';
        if (! WorkosConfiguration::enabled() || $request->has('error')
            || ! is_string($request->query('code')) || $request->query('code') === '') {
            return to_route($destination)->with('workos_error', 'WorkOS login was not completed. Please try again.');
        }

        try {
            $identity = app(WorkosGateway::class)->authenticate($request->query('code'), $flow['verifier']);
            $user = $resolveUser->handle($identity, $linkTo);
        } catch (ValidationException $exception) {
            return to_route($destination)->with('workos_error', $exception->errors()['workos'][0]);
        } catch (Throwable $exception) {
            return $this->failure($exception, $destination);
        }

        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'workos_authenticated' => true,
            'workos_session' => $identity->sealedSession,
            'workos_session_id' => $identity->sessionId,
        ]);

        return $linkTo
            ? to_route('profile.edit')->with('status', 'WorkOS connected successfully.')
            : redirect()->intended(route('dashboard', absolute: false));
    }

    private function failure(Throwable $exception, string $destination = 'login'): Response
    {
        // Provider exception messages may contain credentials, codes or response bodies.
        Log::warning('WorkOS authentication failed.', ['exception_type' => $exception::class]);

        return to_route($destination)->with('workos_error', 'WorkOS is currently unavailable. Please try again or use email and password.');
    }
}

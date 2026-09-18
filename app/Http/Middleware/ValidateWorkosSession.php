<?php

namespace App\Http\Middleware;

use App\Domains\Uas\Access\Application\WorkosConfiguration;
use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidateWorkosSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Logout remains available after expiry or provider failure.
        if (! $request->user() || ! $request->session()->get('workos_authenticated') || $request->routeIs('logout')) {
            return $next($request);
        }

        try {
            if (! WorkosConfiguration::enabled() || ! $request->session()->get('workos_session')) {
                throw new RuntimeException('WorkOS session is unavailable.');
            }

            $identity = app(WorkosGateway::class)->validateSession($request->session()->get('workos_session'));

            if (! $identity->emailVerified || $identity->id !== $request->user()->workos_id) {
                throw new RuntimeException('WorkOS identity does not match the app session.');
            }

            $request->session()->put([
                'workos_session' => $identity->sealedSession,
                'workos_session_id' => $identity->sessionId,
            ]);
        } catch (Throwable) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->with('workos_error', 'Your WorkOS session ended. Please log in again.');
        }

        return $next($request);
    }
}

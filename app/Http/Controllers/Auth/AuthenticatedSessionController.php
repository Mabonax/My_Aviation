<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Uas\Access\Application\WorkosConfiguration;
use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'workosEnabled' => WorkosConfiguration::enabled(),
            'workosError' => $request->session()->get('workos_error'),
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->forget(['workos_authenticated', 'workos_session', 'workos_session_id', 'workos_oauth']);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): HttpResponse
    {
        $logoutUrl = null;
        if ($request->session()->get('workos_authenticated') && $request->session()->get('workos_session_id')) {
            try {
                $logoutUrl = app(WorkosGateway::class)->logoutUrl($request->session()->get('workos_session_id'));
            } catch (Throwable) {
                // A configuration or provider failure must not prevent local logout.
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $logoutUrl ? Inertia::location($logoutUrl) : redirect('/');
    }
}

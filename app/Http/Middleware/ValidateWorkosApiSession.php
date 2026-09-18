<?php

namespace App\Http\Middleware;

use App\Domains\Uas\Access\Application\WorkosConfiguration;
use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Domain\Models\WorkosMobileSession;
use App\Domains\Uas\Api\Application\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidateWorkosApiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token || ! in_array('workos', $token->abilities ?? [], true) || $request->routeIs('api.v1.auth.logout')) {
            return $next($request);
        }
        try {
            Cache::lock('workos:token:'.$token->id, 60)->block(5, function () use ($token, $request) {
                $session = WorkosMobileSession::where('personal_access_token_id', $token->id)->first();
                if (! $session || ! WorkosConfiguration::enabled()) {
                    throw new RuntimeException('Missing WorkOS mobile session.');
                }
                $identity = app(WorkosGateway::class)->validateSession($session->sealed_session);
                if (! $identity->emailVerified || $identity->id !== $request->user()->workos_id) {
                    throw new RuntimeException('Invalid WorkOS mobile identity.');
                }
                $session->update(['sealed_session' => $identity->sealedSession, 'session_id' => $identity->sessionId]);
            });
        } catch (Throwable) {
            $token->delete();
            return ApiResponse::error('unauthenticated', 'Your WorkOS session ended. Please sign in again.', 401);
        }

        return $next($request);
    }
}

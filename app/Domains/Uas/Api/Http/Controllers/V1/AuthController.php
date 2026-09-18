<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Http\Controllers\Controller;
use App\Domains\Uas\Access\Domain\Contracts\WorkosGateway;
use App\Domains\Uas\Access\Domain\Models\WorkosMobileSession;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $user = $request->user();
        $token = $user->createToken($credentials['device_name'] ?? 'api-v1-client');
        Auth::guard('web')->logout();

        return ApiResponse::success([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 'Authenticated.', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();

        if ($accessToken !== null && method_exists($accessToken, 'delete')) {
            $session = WorkosMobileSession::where('personal_access_token_id', $accessToken->id)->first();
            try {
                if ($session) {
                    app(WorkosGateway::class)->revokeSession($session->session_id);
                }
            } catch (Throwable $e) {
                Log::warning('WorkOS remote logout unavailable; local token revoked.', ['exception_type' => $e::class]);
            } finally {
                $accessToken->delete();
            }
        }

        return ApiResponse::success([], 'Logged out.');
    }
}

<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Access\Application\Actions\MobileWorkosAuthentication;
use App\Domains\Uas\Access\Application\WorkosConfiguration;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class WorkosAuthController extends Controller
{
    public function authorize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'state' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
            'code_challenge' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/'],
            'screen_hint' => ['required', 'in:sign-in,sign-up'],
        ]);
        return $this->respond(fn () => app(MobileWorkosAuthentication::class)->begin($data));
    }

    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'state' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
            'code_verifier' => ['required', 'string', 'regex:/^[A-Za-z0-9._~-]{43,128}$/'],
            'code' => ['required', 'string', 'max:2048'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);
        return $this->respond(fn () => app(MobileWorkosAuthentication::class)->exchange($data), 201);
    }

    private function respond(callable $operation, int $status = 200): JsonResponse
    {
        if (! WorkosConfiguration::enabled()) {
            return ApiResponse::error('workos_unavailable', 'WorkOS login is not configured. Please contact your administrator.', 503);
        }

        try {
            return ApiResponse::success($operation(), null, $status)->header('Cache-Control', 'no-store');
        } catch (ValidationException $e) {
            return ApiResponse::error('validation_failed', $e->errors()['workos'][0] ?? 'Please start sign-in again.', 422, $e->errors());
        } catch (Throwable $e) {
            Log::warning('Mobile WorkOS authentication failed.', ['exception_type' => $e::class]);
            return ApiResponse::error('workos_unavailable', 'WorkOS sign-in could not be completed. Please try again.', 503);
        }
    }
}

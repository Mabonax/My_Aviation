<?php

namespace App\Domains\Uas\Api\Application;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public const CONTRACT_VERSION = 'v1.0';

    public static function success(array $data = [], ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'error' => null,
            'meta' => [
                'contract_version' => self::CONTRACT_VERSION,
            ],
        ], $status);
    }

    public static function error(string $error, ?string $message = null, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'error' => $error,
            'meta' => [
                'contract_version' => self::CONTRACT_VERSION,
            ],
        ], $status);
    }
}

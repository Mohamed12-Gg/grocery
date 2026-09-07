<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public static function success($message = null, $result = null, $code = 200): JsonResponse
    {
        $response = [
            'status' => $code,
            'message' => $message,
            'data' => $result,
        ];

        return response()->json($response, $code);
    }

    public static function error($message = null, $result = null, $code = 404): JsonResponse
    {
        $response = [
            'status' => $code,
            'message' => $message,
            'data' => $result,
        ];

        return response()->json($response, $code);
    }
}

<?php
namespace App\Traits\V1;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
trait ApiResponse
{
    public static function successResponse($message = null, $result = null, $code = 200):JsonResponse
    {
        $response = [
            'status' => $code,
            'message' => $message,
            'data'    => $result,
        ];
        return response()->json($response, $code);
    }

    public static function errorResponse($message = null, $result = null, $code = 404):JsonResponse
    {
        $response = [
            'status' => $code,
            'message' => $message,
            'data'    => $result,
        ];
        return response()->json($response, $code);
    }
}
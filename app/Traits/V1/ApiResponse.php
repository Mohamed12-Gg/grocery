<?php
namespace App\Traits\V1;
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
    public static function successPaginated($message, $collection, $paginator, array $extra = [], $code = 200): JsonResponse
    {
        $response = array_merge(
            [
                'status' => $code,
                'message' => $message,
                'data' => $collection,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            $extra,
        );

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

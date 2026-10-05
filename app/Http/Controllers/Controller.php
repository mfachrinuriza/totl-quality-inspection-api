<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function successResponse(string $message, mixed $data = null, int $httpStatus = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $httpStatus);
    }

    protected function errorResponse(string $message, mixed $data = null, int $httpStatus = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $httpStatus);
    }
}

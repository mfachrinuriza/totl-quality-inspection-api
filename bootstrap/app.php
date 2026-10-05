<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn () => true);
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api', 'api/*')) {
                return null;
            }

            $statusCode = $e instanceof ValidationException
                ? 422
                : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);

            $message = match (true) {
                $e instanceof ValidationException => 'Validasi gagal.',
                $statusCode === 404 => 'Resource tidak ditemukan.',
                $statusCode === 401 => 'Autentikasi diperlukan.',
                $statusCode === 403 => 'Akses ditolak.',
                $statusCode < 500 => 'Permintaan tidak dapat diproses.',
                default => 'Terjadi kesalahan pada server.',
            };

            $data = $e instanceof ValidationException
                ? ['errors' => $e->errors()]
                : null;

            return response()->json([
                'status' => 'error',
                'message' => $message,
                'data' => $data,
            ], $statusCode);
        });
    })->create();

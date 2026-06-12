<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Middleware\AllowFrontendRequests;
use App\Http\Middleware\AuthenticateAccessToken;
use App\Http\Middleware\EnsurePermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            AllowFrontendRequests::class,
        ]);
        $middleware->alias([
            'auth.token' => AuthenticateAccessToken::class,
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $exception->getMessage();
            $previousMessage = $exception->getPrevious()?->getMessage() ?? '';
            $isConnectionError = str_contains($message, 'SQLSTATE[HY000] [2002]')
                || str_contains($previousMessage, 'SQLSTATE[HY000] [2002]')
                || str_contains($message, 'Connection refused')
                || str_contains($previousMessage, 'Connection refused')
                || str_contains($message, 'No se puede establecer una conexion')
                || str_contains($message, 'No se puede establecer una conexión');

            if (! $isConnectionError) {
                return null;
            }

            return response()->json([
                'message' => 'No hay conexion con la base de datos. Verifica que el servicio de MySQL este activo e intenta nuevamente.',
                'code' => 'database_connection_error',
            ], 503);
        });
    })->create();

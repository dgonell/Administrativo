<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta accion.',
                'code' => 'permission_denied',
                'permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}

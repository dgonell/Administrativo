<?php

namespace App\Http\Middleware;

use App\Models\UserAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAccessToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json(['message' => 'Sesion requerida.'], 401);
        }

        $token = UserAccessToken::query()
            ->with('user.roles.permissions', 'user.permissionOverrides')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $token || $token->expires_at->isPast()) {
            $token?->delete();

            return response()->json(['message' => 'La sesion expiro. Inicia sesion nuevamente.'], 401);
        }

        if (! $token->user->is_active) {
            $token->delete();

            return response()->json(['message' => 'El usuario esta desactivado.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('access_token', $token);

        if ($token->user->must_change_password && ! $request->is('api/auth/me', 'api/auth/change-password', 'api/auth/logout')) {
            return response()->json([
                'message' => 'Debes cambiar tu contrasena antes de continuar.',
                'code' => 'password_change_required',
            ], 403);
        }

        return $next($request);
    }
}

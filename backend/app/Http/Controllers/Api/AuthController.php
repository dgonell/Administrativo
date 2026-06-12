<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with(['roles.permissions', 'permissionOverrides'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales invalidas.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'El usuario esta desactivado.'], 403);
        }

        $plainToken = Str::random(80);

        $token = $user->accessTokens()->create([
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'last_used_at' => now(),
            'expires_at' => now()->addHours((int) env('SESSION_HOURS', 10)),
        ]);

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $plainToken,
            'expires_at' => $token->expires_at,
            'user' => $this->userPayload($user->fresh(['roles.permissions', 'permissionOverrides'])),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()->load(['roles.permissions', 'permissionOverrides'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('access_token')?->delete();

        return response()->json(['message' => 'Sesion cerrada correctamente.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return response()->json([
            'message' => 'Contrasena actualizada correctamente.',
            'user' => self::userPayload($request->user()->fresh(['roles.permissions', 'permissionOverrides'])),
        ]);
    }

    public static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'last_login_at' => $user->last_login_at,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values(),
            'permissions' => $user->permissionSlugs(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'users' => User::query()
                ->with(['roles', 'permissionOverrides'])
                ->latest()
                ->get()
                ->map(fn (User $user) => $this->userResource($user)),
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'permissions' => PermissionRegistry::groups(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'must_change_password' => true,
        ]);

        $this->syncAccess($user, $data);

        return response()->json($this->userResource($user->fresh(['roles', 'permissionOverrides'])), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $this->validated($request, $user);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'] ?? true,
            'must_change_password' => $data['must_change_password'] ?? $user->must_change_password,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $this->syncAccess($user, $data);

        if (! $user->is_active) {
            $user->accessTokens()->delete();
        }

        return response()->json($this->userResource($user->fresh(['roles', 'permissionOverrides'])));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes desactivar tu propio usuario.'], 422);
        }

        $user->forceFill(['is_active' => false])->save();
        $user->accessTokens()->delete();

        return response()->json(status: 204);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permission_slugs' => ['array'],
            'permission_slugs.*' => ['string', Rule::exists('permissions', 'slug')],
        ]);

        $role->update(['name' => $data['name']]);
        $role->permissions()->sync(
            Permission::query()->whereIn('slug', $data['permission_slugs'] ?? [])->pluck('id')
        );

        return response()->json($role->fresh('permissions'));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'must_change_password' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
            'permission_overrides' => ['array'],
            'permission_overrides.*.slug' => ['required', 'string', Rule::exists('permissions', 'slug')],
            'permission_overrides.*.allowed' => ['required', 'boolean'],
        ]);
    }

    private function syncAccess(User $user, array $data): void
    {
        $user->roles()->sync($data['role_ids'] ?? []);

        $overrides = collect($data['permission_overrides'] ?? [])
            ->mapWithKeys(function (array $override) {
                $permissionId = Permission::query()->where('slug', $override['slug'])->value('id');

                return [$permissionId => ['allowed' => $override['allowed']]];
            })
            ->filter(fn ($value, $key) => $key);

        $user->permissionOverrides()->sync($overrides);
    }

    private function userResource(User $user): array
    {
        $user->loadMissing(['roles', 'permissionOverrides']);

        return [
            ...AuthController::userPayload($user),
            'role_ids' => $user->roles->pluck('id')->values(),
            'permission_overrides' => $user->permissionOverrides->map(fn (Permission $permission) => [
                'slug' => $permission->slug,
                'allowed' => (bool) $permission->pivot->allowed,
            ])->values(),
            'sessions' => $user->accessTokens()->latest('last_used_at')->get()->map(fn ($token) => [
                'id' => $token->id,
                'ip_address' => $token->ip_address,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
            ]),
        ];
    }
}

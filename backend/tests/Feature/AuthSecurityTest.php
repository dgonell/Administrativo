<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_a_valid_session(): void
    {
        $this->getJson('/api/drivers')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Sesion requerida.');
    }

    public function test_users_without_permission_are_rejected(): void
    {
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $role = Role::query()->create(['name' => 'Solo acceso', 'slug' => 'solo-acceso']);
        $permission = Permission::query()->where('slug', 'system.access')->firstOrFail();
        $role->permissions()->sync([$permission->id]);

        $user = User::query()->create([
            'name' => 'Usuario limitado',
            'email' => 'limitado@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);

        $token = $this->postJson('/api/auth/login', [
            'email' => 'limitado@example.com',
            'password' => 'Password123!',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/drivers')
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_denied');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissionsBySlug = collect(PermissionRegistry::groups())
            ->flatMap(function (array $permissions, string $group) {
                return collect($permissions)->map(fn (string $name, string $slug) => [
                    'name' => $name,
                    'slug' => $slug,
                    'group' => $group,
                ]);
            });

        $permissionsBySlug->each(function (array $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        });

        Permission::query()->where('slug', 'operations.buses.manage')->delete();

        $adminRole = Role::query()->updateOrCreate(
            ['slug' => 'administrador'],
            ['name' => 'Administrador', 'is_system' => true]
        );

        $operatorRole = Role::query()->updateOrCreate(
            ['slug' => 'operador'],
            ['name' => 'Operador', 'is_system' => false]
        );

        $adminRole->permissions()->sync(Permission::query()->pluck('id'));

        $operatorPermissions = Permission::query()
            ->whereIn('slug', [
                'system.access',
                'drivers.view',
                'drivers.create',
                'drivers.update',
                'drivers.photo',
                'drivers.documents.manage',
                'drivers.leaves.manage',
                'drivers.conduct.manage',
                'drivers.termination.manage',
                'finance.quotes.view',
                'finance.quotes.manage',
                'finance.quotes.print',
                'finance.clients.view',
                'finance.routes.view',
                'operations.buses.view',
                'operations.buses.history.view',
                'operations.buses.create',
                'operations.buses.update',
                'operations.buses.photo',
                'operations.buses.mileage.update',
                'operations.buses.driver.assign',
                'operations.buses.status.update',
                'operations.buses.retire',
                'operations.maintenance.view',
                'operations.maintenance.create',
                'fuel.view',
                'fuel.purchases.manage',
                'fuel.dispatches.manage',
                'fuel.measurements.manage',
                'fuel.closures.manage',
                'fuel.adjustments.manage',
                'fuel.partners.manage',
            ])
            ->pluck('id');

        $operatorRole->permissions()->sync($operatorPermissions);

        $adminEmail = env('ADMIN_EMAIL', 'admin@administrativo.local');
        $adminPassword = env('ADMIN_PASSWORD');
        $passwordFile = storage_path('app/admin-password.txt');

        if (! $adminPassword && app()->runningUnitTests()) {
            $adminPassword = 'Admin12345!';
        }

        if (! $adminPassword && File::exists($passwordFile)) {
            $adminPassword = trim(File::get($passwordFile));
        }

        if (! $adminPassword) {
            $adminPassword = Str::password(18);
            File::put($passwordFile, $adminPassword);
        }

        $admin = User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => Hash::make($adminPassword),
                'is_active' => true,
            ]
        );

        if (Hash::check('password', $admin->password)) {
            $admin->forceFill(['password' => Hash::make($adminPassword)])->save();
            $admin->accessTokens()->delete();
        }

        if (! $admin->is_active) {
            $admin->forceFill(['is_active' => true])->save();
        }

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}

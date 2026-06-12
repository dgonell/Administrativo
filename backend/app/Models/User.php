<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_active', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissionOverrides(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission_overrides')
            ->withPivot('allowed');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(UserAccessToken::class);
    }

    public function permissionSlugs(): array
    {
        $this->loadMissing(['roles.permissions', 'permissionOverrides']);

        $permissions = $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();

        foreach ($this->permissionOverrides as $permission) {
            if ($permission->pivot->allowed) {
                $permissions->push($permission->slug);
            } else {
                $permissions = $permissions->reject(fn (string $slug) => $slug === $permission->slug);
            }
        }

        return $permissions->unique()->values()->all();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionSlugs(), true);
    }
}

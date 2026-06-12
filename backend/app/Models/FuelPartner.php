<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelPartner extends Model
{
    protected $fillable = ['name', 'document', 'phone', 'email', 'monthly_quota_liters', 'is_active', 'notes'];

    protected $casts = [
        'monthly_quota_liters' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(FuelPartnerVehicle::class);
    }
}

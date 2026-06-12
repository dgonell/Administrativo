<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelPartnerVehicle extends Model
{
    protected $fillable = ['fuel_partner_id', 'plate', 'brand', 'model', 'tank_capacity_liters', 'expected_efficiency', 'monthly_quota_liters', 'is_active', 'notes'];

    protected $casts = [
        'tank_capacity_liters' => 'decimal:2',
        'expected_efficiency' => 'decimal:3',
        'monthly_quota_liters' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(FuelPartner::class, 'fuel_partner_id');
    }
}

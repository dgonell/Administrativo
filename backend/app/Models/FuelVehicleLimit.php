<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelVehicleLimit extends Model
{
    protected $fillable = [
        'operation_bus_id', 'tank_capacity_liters', 'expected_efficiency',
        'daily_quota_liters', 'weekly_quota_liters', 'monthly_quota_liters',
        'requires_authorization', 'notes',
    ];

    protected $casts = [
        'tank_capacity_liters' => 'decimal:2',
        'expected_efficiency' => 'decimal:3',
        'daily_quota_liters' => 'decimal:2',
        'weekly_quota_liters' => 'decimal:2',
        'monthly_quota_liters' => 'decimal:2',
        'requires_authorization' => 'boolean',
    ];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(OperationBus::class, 'operation_bus_id');
    }
}

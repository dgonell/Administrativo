<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelTankMeasurement extends Model
{
    protected $fillable = ['fuel_tank_id', 'measured_at', 'theoretical_liters', 'physical_liters', 'difference_liters', 'reason', 'notes', 'created_by'];

    protected $casts = [
        'measured_at' => 'datetime',
        'theoretical_liters' => 'decimal:2',
        'physical_liters' => 'decimal:2',
        'difference_liters' => 'decimal:2',
    ];
}

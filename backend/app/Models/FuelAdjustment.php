<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelAdjustment extends Model
{
    protected $fillable = ['fuel_tank_id', 'adjusted_at', 'liters', 'type', 'reason', 'notes', 'created_by'];

    protected $casts = [
        'adjusted_at' => 'datetime',
        'liters' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelHose extends Model
{
    protected $fillable = ['fuel_tank_id', 'code', 'name', 'current_counter', 'allowed_difference_liters', 'status', 'notes'];

    protected $casts = [
        'current_counter' => 'decimal:3',
        'allowed_difference_liters' => 'decimal:3',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(FuelTank::class, 'fuel_tank_id');
    }
}

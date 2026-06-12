<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelTank extends Model
{
    protected $fillable = ['name', 'fuel_type', 'capacity_liters', 'current_liters', 'minimum_liters', 'location', 'status', 'notes'];

    protected $casts = [
        'capacity_liters' => 'decimal:2',
        'current_liters' => 'decimal:2',
        'minimum_liters' => 'decimal:2',
    ];

    public function hoses(): HasMany
    {
        return $this->hasMany(FuelHose::class);
    }
}

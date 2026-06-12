<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelPurchase extends Model
{
    protected $fillable = ['fuel_tank_id', 'received_at', 'supplier', 'invoice_number', 'liters', 'unit_cost', 'total_cost', 'tank_liters_before', 'tank_liters_after', 'difference_liters', 'notes', 'created_by'];

    protected $casts = [
        'received_at' => 'date',
        'liters' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'tank_liters_before' => 'decimal:2',
        'tank_liters_after' => 'decimal:2',
        'difference_liters' => 'decimal:2',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(FuelTank::class, 'fuel_tank_id');
    }
}

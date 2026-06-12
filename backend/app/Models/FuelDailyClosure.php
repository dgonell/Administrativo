<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelDailyClosure extends Model
{
    protected $fillable = ['fuel_hose_id', 'closed_on', 'counter_start', 'counter_end', 'system_liters', 'counter_liters', 'difference_liters', 'status', 'notes', 'created_by'];

    protected $casts = [
        'closed_on' => 'date',
        'counter_start' => 'decimal:3',
        'counter_end' => 'decimal:3',
        'system_liters' => 'decimal:3',
        'counter_liters' => 'decimal:3',
        'difference_liters' => 'decimal:3',
    ];

    public function hose(): BelongsTo
    {
        return $this->belongsTo(FuelHose::class, 'fuel_hose_id');
    }
}

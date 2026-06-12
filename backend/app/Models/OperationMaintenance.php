<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationMaintenance extends Model
{
    protected $fillable = [
        'operation_bus_id', 'service_type', 'service_date', 'mileage', 'workshop',
        'technician', 'labor_cost', 'next_due_date', 'next_due_mileage', 'notes', 'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_due_date' => 'date',
        'mileage' => 'integer',
        'next_due_mileage' => 'integer',
        'labor_cost' => 'decimal:2',
    ];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(OperationBus::class, 'operation_bus_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OperationMaintenanceItem::class);
    }
}

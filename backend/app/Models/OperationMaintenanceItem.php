<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationMaintenanceItem extends Model
{
    protected $fillable = [
        'operation_maintenance_id', 'operation_part_id', 'category', 'name', 'quantity',
        'tire_position', 'brand', 'reference', 'unit_cost', 'notes',
    ];

    protected $casts = ['unit_cost' => 'decimal:2'];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(OperationMaintenance::class, 'operation_maintenance_id');
    }
}

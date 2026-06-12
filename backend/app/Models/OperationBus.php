<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationBus extends Model
{
    protected $fillable = [
        'fleet_number',
        'brand',
        'model',
        'vehicle_type',
        'plate',
        'chassis',
        'year',
        'capacity',
        'color',
        'current_mileage',
        'mileage_updated_at',
        'acquired_at',
        'insurer',
        'driver_id',
        'status',
        'notes',
        'photo_path',
        'legacy_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'capacity' => 'integer',
        'current_mileage' => 'integer',
        'mileage_updated_at' => 'date',
        'acquired_at' => 'date',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(OperationMaintenance::class);
    }
}

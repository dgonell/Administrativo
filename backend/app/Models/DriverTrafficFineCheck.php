<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverTrafficFineCheck extends Model
{
    protected $fillable = [
        'driver_id',
        'license_number',
        'vehicle_plate',
        'checked_at',
        'source',
        'result_status',
        'result_summary',
        'amount',
        'file_path',
        'next_check_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'next_check_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

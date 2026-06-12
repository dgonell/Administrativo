<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverTerminationRecord extends Model
{
    protected $fillable = [
        'driver_id',
        'termination_date',
        'termination_type',
        'reason',
        'description',
        'rehire_status',
        'rehire_reason',
        'file_path',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'termination_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

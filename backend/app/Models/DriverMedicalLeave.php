<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverMedicalLeave extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'leave_type',
        'started_at',
        'ended_at',
        'reason',
        'description',
        'file_path',
        'status',
        'registered_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

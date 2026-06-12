<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverConductReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'event_date',
        'type',
        'severity',
        'description',
        'action_taken',
        'file_path',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

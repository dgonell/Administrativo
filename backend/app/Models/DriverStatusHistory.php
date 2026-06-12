<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverStatusHistory extends Model
{
    protected $fillable = [
        'driver_id',
        'previous_status',
        'new_status',
        'reason',
        'created_by',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

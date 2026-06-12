<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationBusHistory extends Model
{
    protected $fillable = [
        'operation_bus_id',
        'fleet_number',
        'action',
        'detail',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

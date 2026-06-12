<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['driver_id', 'name', 'relationship', 'phone', 'secondary_phone'])]
class DriverEmergencyContact extends Model
{
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverLicense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'license_number',
        'category',
        'issued_at',
        'expires_at',
        'issuing_entity',
        'restrictions',
        'observations',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

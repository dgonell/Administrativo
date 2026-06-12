<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'document_type',
        'name',
        'file_path',
        'file_disk',
        'mime_type',
        'size',
        'issued_at',
        'expires_at',
        'status',
        'notes',
        'uploaded_by',
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

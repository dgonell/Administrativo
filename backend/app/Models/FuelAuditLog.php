<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelAuditLog extends Model
{
    protected $fillable = ['action', 'auditable_type', 'auditable_id', 'before', 'after', 'detail', 'user_id'];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}

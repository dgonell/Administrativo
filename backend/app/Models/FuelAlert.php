<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelAlert extends Model
{
    protected $fillable = ['type', 'severity', 'title', 'message', 'alertable_type', 'alertable_id', 'resolved_at', 'created_by'];

    protected $casts = ['resolved_at' => 'datetime'];
}

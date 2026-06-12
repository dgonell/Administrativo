<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}

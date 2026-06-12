<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceClient extends Model
{
    protected $fillable = ['name', 'rnc', 'contact', 'phone', 'email'];

    public function quotes(): HasMany
    {
        return $this->hasMany(FinanceQuote::class);
    }
}

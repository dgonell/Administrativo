<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceQuoteLine extends Model
{
    protected $fillable = [
        'finance_quote_id',
        'route_name',
        'capacity',
        'days',
        'buses',
        'price_per_bus',
        'final_price',
        'pickup_point',
        'dropoff_point',
        'schedule',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(FinanceQuote::class);
    }
}

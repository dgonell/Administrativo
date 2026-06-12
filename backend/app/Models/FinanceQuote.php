<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceQuote extends Model
{
    protected $fillable = [
        'number',
        'status',
        'finance_client_id',
        'service_date',
        'valid_until',
        'payment_terms',
        'notes',
        'final_price',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'valid_until' => 'date',
            'final_price' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(FinanceClient::class, 'finance_client_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceQuoteLine::class);
    }
}

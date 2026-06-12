<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceRoute extends Model
{
    protected $fillable = ['name', 'distance', 'base_rate'];
}

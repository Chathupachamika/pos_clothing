<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $fillable = [
        'investor_name',
        'amount',
        'investment_date',
        'description',
    ];
}

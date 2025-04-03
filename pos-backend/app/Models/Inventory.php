<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inventory extends Model
{
    protected $fillable = [
        'quantity',
        'restock_date_time',
        'added_stock_amount',
        'location',
        'status'
    ];

    protected $casts = [
        'restock_date_time' => 'datetime',
        'quantity' => 'integer',
        'added_stock_amount' => 'integer'
    ];

    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }
}

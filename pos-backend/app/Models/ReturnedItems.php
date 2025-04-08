<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnedItems extends Model
{
    protected $fillable = [
        'order_id',
        'product_variation_id',
        'quantity',
        'reason',
        'return_date',
        'returned_amount'
    ];

    protected $casts = [
        'return_date' => 'datetime',
        'returned_amount' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariations::class, 'product_variation_id');
    }
}

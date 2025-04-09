<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'bar_code',
        'variation_id',
        'quantity',
        'price',
    ];

    /**
     * Get the order associated with the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product variation associated with the item.
     */
    public function variation()
    {
        return $this->belongsTo(ProductVariations::class, 'variation_id');
    }
}

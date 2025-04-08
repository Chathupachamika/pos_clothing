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
}

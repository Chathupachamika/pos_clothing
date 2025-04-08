<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'time',
        'status',
        'payment_type',
        'amount',
        'discount',
        'customer_id',
    ];

    /**
     * Get the items associated with the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the cashier who processed the order.
     */
    public function cashier()
    {
        return $this->belongsTo(Cashier::class);
    }

    /**
     * Get the customer associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

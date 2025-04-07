<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $fillable = [
        'time',
        'status',
        'payment_type',
        'amount',
        'discount',
        'customer_id',
    ];

    protected $casts = [
        'status' => 'boolean',
        'payment_type' => 'string',
    ];

    // Define the relationship with the Customer model
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Define the relationship with the OrderItem model
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // Helper method to calculate the total amount after discount
    public function calculateFinalAmount()
    {
        $total = $this->orderItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $discountAmount = ($total * $this->discount) / 100;
        return $total - $discountAmount;
    }
}

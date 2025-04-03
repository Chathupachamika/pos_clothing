<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $fillable = [
        'customer_id',
        'cashier_id',
        'amount',
        'payment_type',
        'status',
        'time',
        'discount'
    ];

    protected $casts = [
        'payment_type' => 'string'
    ];

    public function product_sales()
    {
        return $this->hasMany(Product_Sales::class, 'sales_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_sales', 'sales_id', 'product_id')
            ->withPivot('quantity', 'price');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function allowedPaymentTypes()
    {
        return ['CASH', 'CREDIT_CARD', 'DEBIT_CARD'];
    }
}

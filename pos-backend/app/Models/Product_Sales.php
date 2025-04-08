<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_Sales extends Model
{
    protected $table = 'product_sales';

    protected $fillable = [
        'product_id',
        'sales_id',
        'quantity',
        'price',
        'returned_quantity'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'returned_quantity' => 'integer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - ($this->returned_quantity ?? 0);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariations extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'price',
        'quantity',
        'barcode',
        'selling_price',
        'discount',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discount' => 'decimal:2'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

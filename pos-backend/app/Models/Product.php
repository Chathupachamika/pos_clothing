<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name',
        'size',
        'color',
        'category',
        'description',
        'brand_name',
        'location',
        'supplier_id',
        'admin_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'seller_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'discount' => 'decimal:2',
        'quantity' => 'integer',
        'added_stock_amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'bar_code' => 'string'
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // public function grnNotes()
    // {
    //     return $this->hasMany(GRNNote::class);
    // }

    public function image()
    {
        return $this->hasOne(ProductImages::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariations::class, 'product_id');
    }
}

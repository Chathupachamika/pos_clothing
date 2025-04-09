<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $table = 'return_items';

    protected $fillable = [
        'order_item_id',
        'variation_id',
        'reason',
        'quantity',
        'product_id',
        'created_at',
        'updated_at'
    ];

    public function salesReturnItem()
    {
        return $this->hasOne(SalesReturnItem::class, 'return_item_id');
    }
}
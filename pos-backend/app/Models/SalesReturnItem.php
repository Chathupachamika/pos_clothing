<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $table = 'sales_return_items';
    protected $fillable = [
        'order_id',
        'return_item_id',
        'returned_at',
        'created_at',
        'updated_at',
    ];

    public function returnItem()
    {
        return $this->belongsTo(ReturnItem::class, 'return_item_id');
    }
}
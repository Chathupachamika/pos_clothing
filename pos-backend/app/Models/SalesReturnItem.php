<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $table = 'sales_return_item';
    protected $fillable = [
        'sales_id',
        'return_item_id',
        'returned_at',
        'created_at',
        'updated_at',
    ];
}

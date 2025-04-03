<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'product_id', 'name', 'description', 'discount', 'start_date', 'end_date'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

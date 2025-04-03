<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImages extends Model
{
    protected $fillable = ['product_id','path','name','size'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

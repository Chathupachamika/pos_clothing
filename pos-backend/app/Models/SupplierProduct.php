<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    protected $table ='supplier_product';
    protected $fillable = ['supplier_id', 'product_id'];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

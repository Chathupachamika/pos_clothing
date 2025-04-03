<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GRNNote extends Model
{
    protected $table = 'grn_notes';

    protected $fillable = [
        'grn_number',
        'product_id',
        'supplier_id',
        'admin_id',
        'price',
        'product_details',
        'received_date',
        'previous_quantity',
        'new_quantity',
        'adjusted_quantity',
        'adjustment_type'
    ];

    protected $casts = [
        'product_details' => 'array',
        'received_date' => 'datetime',
        'price' => 'decimal:2',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'adjusted_quantity' => 'integer'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}

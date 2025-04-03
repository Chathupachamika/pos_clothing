<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierAdmin extends Model
{
    protected $fillable = ['supplier_id', 'admin_id','alert_message'];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class,'supplier_id');
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }
}

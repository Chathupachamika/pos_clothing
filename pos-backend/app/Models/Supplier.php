<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['id', 'admin_id', 'name', 'contact', 'email'];

    public function grnNotes()
    {
        return $this->hasMany(GRNNote::class);
    }
}

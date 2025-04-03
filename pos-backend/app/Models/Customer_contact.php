<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer_contact extends Model
{
    use SoftDeletes;
    protected $table = 'customer_contact';
    protected $fillable = [
        'contact_number',
        'customer_id'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use SoftDeletes;
    protected $table = 'feedback';
    protected $fillable = [
        'customer_id',
        'comment',
        'rating'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

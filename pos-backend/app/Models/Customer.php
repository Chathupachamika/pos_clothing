<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'contact_number',
    ];

    public function contact()
    {
        return $this->hasMany(Customer_contact::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cashier extends Model
{
    use SoftDeletes;
    protected $table = 'cashier';
    protected $fillable = [
        'name',
        'email',
        'password',
        'image_path'
    ];

    protected $hidden = ['password'];
}

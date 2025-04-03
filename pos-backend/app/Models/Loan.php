<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'borrower_name',
        'amount',
        'loan_date',
        'due_date',
        'status',
        'description',
    ];
}

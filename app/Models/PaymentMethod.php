<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_method';
    protected $fillable = [
        'name',
        'bank',
        'account_name',
        'account_number',
    ];
}

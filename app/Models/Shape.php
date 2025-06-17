<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shape extends Model
{
    protected $table = 'shapes';
    protected $fillable = [
        'name',
        'code',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colour extends Model
{
    protected $table = 'colours';
    protected $fillable = [
        'name',
        'code',
    ];
}

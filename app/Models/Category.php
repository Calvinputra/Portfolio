<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = [
        'name',
        'code',
    ];

    // public function classification()
    // {
    //     return $this->hasOne('App\Models\Classification', 'categories_id', 'code');
    // }
}

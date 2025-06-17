<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    protected $table = 'classifications';
    protected $fillable = [
        'name',
        'categories_id',
        'code',
    ];

    public function categories()
    {
        return $this->hasOne('App\Models\Category', 'code', 'categories_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    protected $table = 'roles_user';
    protected $fillable = [
        'name',
    ];
}

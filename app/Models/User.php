<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email', 'password', 'api_token'];
    protected $hidden = ['password', 'api_token'];
}

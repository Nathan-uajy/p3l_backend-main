<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    public $timestamps = false;

    protected $fillable = ['email', 'token'];

    protected $table = 'password_resets';

    public $incrementing = false;
    protected $primaryKey = 'email';
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['contact1', 'contact2', 'email', 'address', 'mon_fri', 'saturday', 'sunday'];
}

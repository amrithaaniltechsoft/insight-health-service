<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = ['category', 'product_name', 'price', 'description', 'image'];
}

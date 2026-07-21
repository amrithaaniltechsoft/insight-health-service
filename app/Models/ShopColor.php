<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopColor extends Model
{
    protected $fillable = ['shop_id', 'color_name', 'image'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}

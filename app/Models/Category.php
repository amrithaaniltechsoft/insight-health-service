<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'description',
        'promo_title', 'promo_description',
        'promo_link_text', 'promo_link_href', 'promo_bg_type',
    ];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class)->orderBy('order');
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}

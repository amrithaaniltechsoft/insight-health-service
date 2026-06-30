<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'description'];

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

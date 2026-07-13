<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_id', 'sub_category_id', 'service_name', 'title', 'service_overview', 'price', 'appointment',
        'faq_link', 'description1', 'description2', 'package_include', 'turn_around_time', 'video_link', 'image'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
}

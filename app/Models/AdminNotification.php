<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clinic_id',
        'actor_id',
        'title',
        'message',
        'type',
        'event_type',
        'priority',
        'is_read',
        'link',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}


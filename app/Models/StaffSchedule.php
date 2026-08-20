<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'clinic_id',
        'shift_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}

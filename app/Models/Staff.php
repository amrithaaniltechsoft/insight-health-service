<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'staff_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'role',
        'clinic_id',
        'specialization',
        'status',
        'working_days',
    ];

    protected $casts = [
        'working_days' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class, 'clinician_id');
    }

    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class, 'staff_id');
    }
}

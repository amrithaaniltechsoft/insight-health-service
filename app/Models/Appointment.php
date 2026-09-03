<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_code',
        'patient_id',
        'clinic_id',
        'service_id',
        'service_ids',
        'staff_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'payment_status',
        'source',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'service_ids' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'patient_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function clinicalNote()
    {
        return $this->hasOne(ClinicalNote::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

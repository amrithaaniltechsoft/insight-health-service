<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_code',
        'customer_id',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'email',
        'phone',
        'address',
        'emergency_contact',
        'allergies',
        'medical_history',
        'status',
    ];

    protected $casts = [
        'emergency_contact' => 'array',
        'allergies' => 'array',
        'medical_history' => 'array',
        'dob' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

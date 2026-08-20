<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'clinician_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'internal_notes',
        'allergies',
        'status',
        'signed_at',
    ];

    protected $casts = [
        'allergies' => 'array',
        'signed_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinician()
    {
        return $this->belongsTo(Staff::class, 'clinician_id');
    }
}

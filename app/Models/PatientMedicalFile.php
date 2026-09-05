<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientMedicalFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'uploaded_by_staff_id',
        'title',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploader()
    {
        return $this->belongsTo(Staff::class, 'uploaded_by_staff_id');
    }
}

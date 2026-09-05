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

    protected static function booted()
    {
        static::creating(function ($patient) {
            if (empty($patient->patient_code)) {
                $patient->patient_code = static::generateNextPatientCode();
            }
        });
    }

    public static function generateNextPatientCode(): string
    {
        $lastPatient = static::orderBy('id', 'desc')->first();
        $nextId = $lastPatient ? ($lastPatient->id + 1) : 1;

        $maxCodeNum = static::where('patient_code', 'like', 'PAT-%')
            ->get()
            ->map(function ($p) {
                return intval(preg_replace('/[^0-9]/', '', $p->patient_code));
            })
            ->max() ?? 0;

        if ($maxCodeNum >= $nextId) {
            $nextId = $maxCodeNum + 1;
        }

        return 'PAT-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

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

    public function medicalFiles()
    {
        return $this->hasMany(PatientMedicalFile::class);
    }

    public function clinicalReports()
    {
        return $this->hasMany(PatientClinicalReport::class);
    }
}

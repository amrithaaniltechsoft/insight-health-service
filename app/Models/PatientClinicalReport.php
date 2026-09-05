<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PatientClinicalReport extends Model
{
    use HasFactory;

    protected $table = 'patient_clinical_reports';

    protected $fillable = [
        'report_code',
        'patient_id',
        'appointment_id',
        'clinician_id',
        'report_type',
        'display_title',
        'report_data',
        'summary_findings',
        'recommendations',
        'status',
        'signed_at',
    ];

    protected $casts = [
        'report_data' => 'array',
        'signed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($report) {
            if (empty($report->report_code)) {
                $maxId = static::max('id') ?? 0;
                $report->report_code = 'REP-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function clinician()
    {
        return $this->belongsTo(Staff::class, 'clinician_id');
    }
}

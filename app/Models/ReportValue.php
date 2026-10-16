<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportValue extends Model
{
    use HasFactory;

    protected $table = 'report_values';

    protected $fillable = [
        'clinical_report_id',
        'patient_id',
        'report_type',
        'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];

    public function clinicalReport()
    {
        return $this->belongsTo(PatientClinicalReport::class, 'clinical_report_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}

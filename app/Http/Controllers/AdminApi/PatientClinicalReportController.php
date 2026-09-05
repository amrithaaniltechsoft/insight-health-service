<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientClinicalReport;
use App\Models\PatientMedicalFile;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PatientClinicalReportController extends Controller
{
    public function index($patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        $reports = PatientClinicalReport::where('patient_id', $patient->id)
            ->with(['clinician', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'report_code' => $r->report_code,
                    'patient_id' => $r->patient_id,
                    'appointment_id' => $r->appointment_id,
                    'report_type' => $r->report_type,
                    'display_title' => $r->display_title,
                    'report_data' => $r->report_data,
                    'summary_findings' => $r->summary_findings,
                    'recommendations' => $r->recommendations,
                    'status' => $r->status,
                    'clinician' => $r->clinician ? trim($r->clinician->first_name . ' ' . $r->clinician->last_name) : 'Clinician',
                    'signed_at' => $r->signed_at ? $r->signed_at->format('Y-m-d H:i') : null,
                    'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i') : '',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    public function store(Request $request, $patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        $validated = $request->validate([
            'report_type' => 'required|string|max:255',
            'display_title' => 'required|string|max:255',
            'report_data' => 'nullable|array',
            'summary_findings' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'appointment_id' => 'nullable',
            'status' => 'nullable|string',
        ]);

        $staffId = null;
        if (auth()->check()) {
            $user = auth()->user();
            if (isset($user->staff_id)) {
                $staffId = $user->staff_id;
            }
        }

        $report = PatientClinicalReport::create([
            'patient_id' => $patient->id,
            'appointment_id' => $validated['appointment_id'] ? intval(preg_replace('/[^0-9]/', '', $validated['appointment_id'])) : null,
            'clinician_id' => $staffId,
            'report_type' => $validated['report_type'],
            'display_title' => $validated['display_title'],
            'report_data' => $validated['report_data'] ?? [],
            'summary_findings' => $validated['summary_findings'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'status' => $validated['status'] ?? 'Finalized',
            'signed_at' => Carbon::now(),
        ]);

        // Also register an entry in patient_medical_files under category 'report'
        PatientMedicalFile::create([
            'patient_id' => $patient->id,
            'appointment_id' => $report->appointment_id,
            'uploaded_by_staff_id' => $staffId,
            'title' => $report->display_title,
            'file_name' => $report->report_code . '.pdf',
            'file_path' => 'reports/' . $report->report_code . '.pdf',
            'file_type' => 'report',
            'mime_type' => 'application/pdf',
            'file_size' => 1024 * 45, // 45 KB virtual generated document size
            'notes' => 'Clinical report generated via Ultrasound Template Engine',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Clinical ultrasound report generated and saved successfully',
            'data' => $report
        ], 201);
    }

    public function show($patientId, $reportId)
    {
        $report = PatientClinicalReport::with(['clinician', 'patient', 'appointment'])
            ->where('id', $reportId)
            ->orWhere('report_code', $reportId)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }
}

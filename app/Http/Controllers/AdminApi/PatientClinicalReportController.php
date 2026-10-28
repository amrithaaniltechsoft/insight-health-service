<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientClinicalReport;
use App\Models\PatientMedicalFile;
use App\Models\ReportValue;
use App\Models\Service;
use App\Services\ClinicalReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
                    'pdf_url' => Storage::disk('public')->url('reports/' . $r->report_code . '.pdf'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    /**
     * Report-type options for the "Create Clinical Report" page.
     *
     * Instead of dumping the whole service catalog, return only the services
     * this patient actually booked (so the clinician reports the test that was
     * performed) plus the services that already have a report, so those can be
     * hidden and duplicates avoided.
     */
    public function reportOptions(Request $request, $patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::with('appointments')
            ->where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        // Determine if the current user is a clinician and restrict to their assigned services.
        $user = $request->user();
        $staff = null;
        if ($user) {
            $staff = \App\Models\Staff::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
        }
        $isClinician = ($staff && $staff->role === 'clinician') || ($user && isset($user->role) && $user->role === 'clinician');
        $clinicianStaffId = $staff ? $staff->id : null;

        // Every service id referenced by the patient's appointments. Bookings
        // may carry a single `service_id` and/or a `service_ids` array.
        $serviceIds = [];
        foreach ($patient->appointments as $apt) {
            // If the current user is a clinician, only include services from appointments assigned to them.
            if ($isClinician && $clinicianStaffId) {
                if ((int) $apt->staff_id !== (int) $clinicianStaffId) {
                    continue;
                }
            }

            if (is_array($apt->service_ids)) {
                foreach ($apt->service_ids as $sid) {
                    $sid = intval($sid);
                    if ($sid > 0) {
                        $serviceIds[$sid] = true;
                    }
                }
            }
            if (!empty($apt->service_id)) {
                $serviceIds[intval($apt->service_id)] = true;
            }
        }

        $bookedServices = [];
        if (!empty($serviceIds)) {
            $bookedServices = Service::whereIn('id', array_keys($serviceIds))
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => $s->id,
                        // `report_type` is stored as the service_name, so keep
                        // this the canonical value the picker should use.
                        'service_name' => $s->service_name ?: $s->title,
                        'title' => $s->title ?: $s->service_name,
                    ];
                })
                ->values();
        }

        $reportedServices = PatientClinicalReport::where('patient_id', $patient->id)
            ->pluck('report_type')
            ->filter()
            ->map(fn ($name) => trim($name))
            ->unique()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'booked_services' => $bookedServices,
                'reported_services' => $reportedServices,
            ],
        ]);
    }

    public function store(Request $request, $patientId, ClinicalReportPdfService $pdfService)
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
            'appointment_id' => !empty($validated['appointment_id']) ? intval(preg_replace('/[^0-9]/', '', $validated['appointment_id'])) : null,
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
        $medicalFile = PatientMedicalFile::create([
            'patient_id' => $patient->id,
            'appointment_id' => $report->appointment_id,
            'uploaded_by_staff_id' => $staffId,
            'title' => $report->display_title,
            'file_name' => $report->report_code . '.pdf',
            'file_path' => 'reports/' . $report->report_code . '.pdf',
            'file_type' => 'report',
            'mime_type' => 'application/pdf',
            'file_size' => 1024 * 45, // placeholder until the PDF is rendered below
            'notes' => 'Clinical report generated via Ultrasound Template Engine',
        ]);

        // Render the PDF now so it is ready to stream later ("call pdf").
        // A failure here must never lose the saved report.
        try {
            $relative = $pdfService->generate($report);
            $medicalFile->update([
                'file_size' => Storage::disk('public')->size($relative),
            ]);
        } catch (\Throwable $e) {
            Log::error('Clinical report PDF generation failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);
        }


        // Store the raw form field values in `report_values` as JSON.
        ReportValue::create([
            'clinical_report_id' => $report->id,
            'patient_id' => $patient->id,
            'report_type' => $report->report_type,
            'values' => $report->report_data ?? [],
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

    public function pdf($patientId, $reportId, ClinicalReportPdfService $pdfService)
    {
        $report = PatientClinicalReport::with('patient')
            ->where('id', $reportId)
            ->orWhere('report_code', $reportId)
            ->firstOrFail();

        $path = $pdfService->path($report);
        if (!$path || !file_exists($path)) {
            $relative = $pdfService->generate($report);
            $path = Storage::disk('public')->path($relative);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $report->report_code . '.pdf"',
        ]);
    }

    /**
     * Delete a clinical report and everything derived from it: the raw values
     * in `report_values`, the vault entry in `patient_medical_files` and the
     * generated PDF on disk. Without this, removing the report values would
     * leave the PDF (and the report) behind.
     */
    public function destroy($patientId, $reportId)
    {
        $report = PatientClinicalReport::where('id', $reportId)
            ->orWhere('report_code', $reportId)
            ->firstOrFail();

        $reportCode = $report->report_code;

        // 1. Raw form values.
        ReportValue::where('clinical_report_id', $report->id)->delete();

        // 2. Vault entry + stored PDF linked to this report.
        $files = PatientMedicalFile::where('patient_id', $report->patient_id)
            ->where('file_name', $reportCode . '.pdf')
            ->get();

        foreach ($files as $file) {
            if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            $file->delete();
        }

        // 3. Safety net: a stray PDF with no matching vault row.
        $pdfPath = 'reports/' . $reportCode . '.pdf';
        if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
        }

        $report->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Clinical report deleted successfully'
        ]);
    }

    /**
     * The Early Pregnancy template is the only form currently persisted to
     * `report_values`. Additional templates can be added here later.
     */
    private function isEarlyPregnancyReport(PatientClinicalReport $report): bool
    {
        $haystack = strtolower(($report->report_type ?? '') . ' ' . ($report->display_title ?? ''));

        return str_contains($haystack, 'early pregnancy');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\PatientClinicalReport;
use App\Services\ClinicalReportPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientReportController extends Controller
{
    /**
     * Lists the clinical reports created through the admin portal for every
     * patient that belongs to a customer.
     *
     * Mirrors BookingController@index: the customer is resolved from the email
     * the patient portal stores in localStorage, because the database lives on
     * this host and the deployed frontend cannot reach it directly.
     */
    public function index(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->query('customer_email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'A valid email address is required.'], 422);
        }

        $customer = Customer::where('email', $email)->first();

        // No customer record means no reports to show — an empty list, not an error.
        if (!$customer) {
            return response()->json(['reports' => [], 'customer_found' => false]);
        }

        $query = PatientClinicalReport::query()
            ->whereHas('patient', fn ($q) => $q->where('customer_id', $customer->id))
            ->with('patient');

        $patientId = $request->query('patient_id');
        if (is_numeric($patientId)) {
            $query->where('patient_id', (int) $patientId);
        }

        $reports = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (PatientClinicalReport $report) {
                $patient = $report->patient;

                return [
                    'id'               => (int) $report->id,
                    'report_code'      => $report->report_code,
                    'patient_id'       => (int) $report->patient_id,
                    'patient_code'     => $patient?->patient_code,
                    'patient_name'     => $patient
                        ? collect([$patient->title, $patient->first_name, $patient->last_name])
                            ->filter()->implode(' ')
                        : null,
                    'dob'              => $patient?->dob?->format('Y-m-d'),
                    'appointment_id'   => $report->appointment_id ? (int) $report->appointment_id : null,
                    'report_type'      => $report->report_type,
                    'display_title'    => $report->display_title,
                    'report_data'      => $report->report_data,
                    'summary_findings' => $report->summary_findings,
                    'recommendations'  => $report->recommendations,
                    'status'           => $report->status,
                    'signed_at'        => $report->signed_at?->format('Y-m-d'),
                    'created_at'       => $report->created_at?->format('Y-m-d'),
                    'pdf_url'          => Storage::disk('public')->url('reports/' . $report->report_code . '.pdf'),
                ];
            })
            ->values();

        return response()->json([
            'reports'        => $reports,
            'customer_found' => true,
        ]);
    }

    /**
     * Streams the stored PDF for one of the customer's reports. If the file has
     * not been rendered yet (e.g. a report saved before this feature existed) it
     * is generated on demand and then served.
     */
    public function pdf(Request $request, string $id, ClinicalReportPdfService $pdfService)
    {
        $email = strtolower(trim((string) $request->query('customer_email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'A valid email address is required.'], 422);
        }

        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            abort(404);
        }

        $report = PatientClinicalReport::with('patient')
            ->whereHas('patient', fn ($q) => $q->where('customer_id', $customer->id))
            ->where(function ($q) use ($id) {
                $q->where('report_code', $id);
                if (is_numeric($id)) {
                    $q->orWhere('id', (int) $id);
                }
            })
            ->first();

        if (!$report) {
            abort(404);
        }

        $path = $pdfService->path($report);

        if (!$path) {
            try {
                $relative = $pdfService->generate($report);
                $path = Storage::disk('public')->path($relative);
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'Unable to generate this report PDF.'], 500);
            }
        }

        return response()->download($path, $report->report_code . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

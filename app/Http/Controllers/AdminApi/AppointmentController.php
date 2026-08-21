<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'service', 'staff', 'clinic']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date') && $request->date) {
            $query->whereDate('appointment_date', $request->date);
        }

        if ($request->has('clinic_id') && $request->clinic_id) {
            $query->where('clinic_id', $request->clinic_id);
        }

        if ($request->has('staff_id') && $request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($apt) {
                return [
                    'id' => $apt->appointment_code,
                    'rawId' => $apt->id,
                    'patientName' => $apt->patient ? ($apt->patient->first_name . ' ' . $apt->patient->last_name) : 'N/A',
                    'patientId' => $apt->patient ? $apt->patient->patient_code : '',
                    'patientEmail' => $apt->patient ? $apt->patient->email : '',
                    'patientPhone' => $apt->patient ? $apt->patient->phone : '',
                    'service' => $apt->service ? ($apt->service->title ?? $apt->service->service_name) : 'Service Scan',
                    'serviceId' => $apt->service_id,
                    'date' => $apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : '',
                    'time' => $apt->start_time,
                    'status' => $apt->status,
                    'paymentStatus' => $apt->payment_status,
                    'clinician' => $apt->staff ? ($apt->staff->first_name . ' ' . $apt->staff->last_name) : 'Unassigned',
                    'staffId' => $apt->staff_id,
                    'notes' => $apt->notes,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $appointments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required',
            'service_id' => 'required',
            'appointment_date' => 'required|date',
            'start_time' => 'required',
            'clinic_id' => 'nullable',
            'staff_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        // Find customer if integer ID vs code passed
        $patient = Customer::where('id', $validated['patient_id'])
            ->orWhere('customer_code', $validated['patient_id'])
            ->first();

        if (!$patient) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        if (!empty($validated['staff_id'])) {
            $assignedStaff = Staff::where('id', $validated['staff_id'])->orWhere('staff_code', $validated['staff_id'])->first();
            if ($assignedStaff) {
                if ($assignedStaff->status === 'inactive' || $assignedStaff->status === 'revoked') {
                    return response()->json(['message' => 'Cannot assign appointment: Staff account access is revoked.'], 422);
                }
                if ($assignedStaff->availability === 'unavailable') {
                    return response()->json(['message' => 'Cannot assign appointment: Clinician is currently marked as Unavailable.'], 422);
                }
            }
        }

        $code = 'APT-' . rand(1000, 9999);

        $appointment = Appointment::create([
            'appointment_code' => $code,
            'patient_id' => $patient->id,
            'service_id' => $validated['service_id'],
            'clinic_id' => $validated['clinic_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'],
            'status' => 'Scheduled',
            'payment_status' => 'Pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment created successfully',
            'data' => $appointment
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->orWhere('appointment_code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'staff_id' => 'nullable',
            'appointment_date' => 'nullable|date',
            'start_time' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $appointment->update(array_filter($validated));

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment updated successfully',
            'data' => $appointment
        ]);
    }

    public function calendar(Request $request)
    {
        $appointments = Appointment::with(['patient', 'service', 'staff'])
            ->get()
            ->map(function ($apt) {
                return [
                    'id' => $apt->appointment_code,
                    'title' => ($apt->patient ? ($apt->patient->first_name . ' ' . $apt->patient->last_name) : 'Patient') . ' - ' . ($apt->service ? ($apt->service->title ?? $apt->service->service_name) : 'Scan'),
                    'start' => ($apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : date('Y-m-d')) . 'T' . $apt->start_time,
                    'status' => $apt->status,
                    'clinician' => $apt->staff ? ($apt->staff->first_name . ' ' . $apt->staff->last_name) : 'Unassigned',
                ];
            });

        return response()->json([
            'status' => 'success',
            'events' => $appointments
        ]);
    }
}

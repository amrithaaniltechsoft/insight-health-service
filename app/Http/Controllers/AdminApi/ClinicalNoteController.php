<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ClinicalNoteController extends Controller
{
    public function showByAppointment($appointmentId)
    {
        $appointment = Appointment::with(['patient', 'service', 'staff'])
            ->where('id', $appointmentId)
            ->orWhere('appointment_code', $appointmentId)
            ->firstOrFail();

        $note = ClinicalNote::where('appointment_id', $appointment->id)->first();

        return response()->json([
            'status' => 'success',
            'patientContext' => [
                'id' => $appointment->patient ? $appointment->patient->patient_code : '',
                'name' => $appointment->patient ? ($appointment->patient->first_name . ' ' . $appointment->patient->last_name) : 'Patient',
                'dob' => $appointment->patient && $appointment->patient->dob ? $appointment->patient->dob->format('Y-m-d') : '',
                'service' => $appointment->service ? $appointment->service->title : '',
                'time' => $appointment->start_time,
                'clinician' => $appointment->staff ? ($appointment->staff->first_name . ' ' . $appointment->staff->last_name) : '',
            ],
            'note' => $note ? [
                'subjective' => $note->subjective,
                'objective' => $note->objective,
                'assessment' => $note->assessment,
                'plan' => $note->plan,
                'internalNotes' => $note->internal_notes,
                'allergies' => $note->allergies ?? [],
                'status' => $note->status,
                'signedAt' => $note->signed_at ? $note->signed_at->format('Y-m-d H:i') : null,
            ] : [
                'subjective' => '',
                'objective' => '',
                'assessment' => '',
                'plan' => '',
                'internalNotes' => '',
                'allergies' => ['Penicillin'],
                'status' => 'Draft',
                'signedAt' => null,
            ]
        ]);
    }

    public function storeOrUpdate(Request $request, $appointmentId)
    {
        $appointment = Appointment::where('id', $appointmentId)
            ->orWhere('appointment_code', $appointmentId)
            ->firstOrFail();

        $validated = $request->validate([
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'internalNotes' => 'nullable|string',
            'allergies' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        $isFinalizing = ($validated['status'] ?? '') === 'Finalized';

        $note = ClinicalNote::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'clinician_id' => $appointment->staff_id,
                'subjective' => $validated['subjective'] ?? null,
                'objective' => $validated['objective'] ?? null,
                'assessment' => $validated['assessment'] ?? null,
                'plan' => $validated['plan'] ?? null,
                'internal_notes' => $validated['internalNotes'] ?? null,
                'allergies' => $validated['allergies'] ?? [],
                'status' => $isFinalizing ? 'Finalized' : 'Draft',
                'signed_at' => $isFinalizing ? Carbon::now() : null,
            ]
        );

        if ($isFinalizing) {
            $appointment->update(['status' => 'Completed']);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isFinalizing ? 'Clinical note finalized and signed' : 'Draft saved successfully',
            'data' => $note
        ]);
    }
}

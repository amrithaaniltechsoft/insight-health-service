<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\Appointment;
use App\Models\Patient;
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
        $patient = $appointment->patient;

        $allergies = [];
        if ($patient && $patient->allergies) {
            $allergies = is_array($patient->allergies) ? $patient->allergies : json_decode($patient->allergies, true) ?? [];
        }
        if (empty($allergies) && $note && $note->allergies) {
            $allergies = is_array($note->allergies) ? $note->allergies : json_decode($note->allergies, true) ?? [];
        }

        $clinicianName = $note->clinician_name ?? null;
        if (empty($clinicianName) && $appointment->staff) {
            $clinicianName = trim($appointment->staff->first_name . ' ' . $appointment->staff->last_name);
        }
        if (empty($clinicianName)) {
            $clinicianName = 'Dr. Marcus Thorne';
        }

        return response()->json([
            'status' => 'success',
            'patientContext' => [
                'id' => $patient ? ($patient->patient_code ?? ('PAT-' . str_pad($patient->id, 4, '0', STR_PAD_LEFT))) : '',
                'rawId' => $patient ? $patient->id : null,
                'name' => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'Patient',
                'dob' => $patient && $patient->dob ? (is_string($patient->dob) ? $patient->dob : $patient->dob->format('Y-m-d')) : '',
                'service' => $appointment->service ? ($appointment->service->title ?? $appointment->service->service_name) : 'Ultrasound Scan',
                'time' => $appointment->start_time ? date('g:i A', strtotime($appointment->start_time)) : '',
                'clinician' => $clinicianName,
                'medicalNotes' => $patient ? ($patient->medical_history ?? '') : '',
                'internalStaffNote' => $note ? ($note->internal_notes ?? '') : ($appointment->internal_notes ?? ''),
                'patientNote' => $appointment->notes ?? '',
                'allergies' => $allergies,
            ],
            'note' => $note ? [
                'subjective' => $note->subjective,
                'objective' => $note->objective,
                'assessment' => $note->assessment,
                'plan' => $note->plan,
                'internalNotes' => $note->internal_notes,
                'allergies' => $allergies,
                'status' => $note->status,
                'clinician_name' => $clinicianName,
                'signedAt' => $note->signed_at ? (is_string($note->signed_at) ? $note->signed_at : $note->signed_at->format('Y-m-d H:i')) : null,
            ] : [
                'subjective' => '',
                'objective' => '',
                'assessment' => '',
                'plan' => '',
                'internalNotes' => '',
                'allergies' => $allergies,
                'status' => 'Draft',
                'clinician_name' => $clinicianName,
                'signedAt' => null,
            ]
        ]);
    }

    public function indexByPatient($patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::with(['customer', 'appointments.service', 'appointments.staff', 'clinicalNotes'])
            ->where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        $allergies = [];
        if ($patient->allergies) {
            $allergies = is_array($patient->allergies) ? $patient->allergies : json_decode($patient->allergies, true) ?? [];
        }

        $latestAppointment = $patient->appointments ? $patient->appointments->last() : null;

        $notes = ClinicalNote::where('patient_id', $patient->id)
            ->with(['clinician', 'appointment.staff', 'appointment.service'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($n) {
                $authorName = $n->clinician_name;
                if (empty($authorName) && $n->clinician) {
                    $authorName = trim($n->clinician->first_name . ' ' . $n->clinician->last_name);
                }
                if (empty($authorName) && $n->appointment && $n->appointment->staff) {
                    $authorName = trim($n->appointment->staff->first_name . ' ' . $n->appointment->staff->last_name);
                }
                if (empty($authorName)) {
                    $authorName = 'Dr. Marcus Thorne';
                }

                return [
                    'id' => $n->id,
                    'date' => $n->created_at ? $n->created_at->format('Y-m-d') : '',
                    'author' => $authorName,
                    'clinician_name' => $authorName,
                    'service' => $n->appointment && $n->appointment->service ? ($n->appointment->service->title ?? $n->appointment->service->service_name) : 'Consultation',
                    'subjective' => $n->subjective,
                    'objective' => $n->objective,
                    'assessment' => $n->assessment,
                    'plan' => $n->plan,
                    'internal_notes' => $n->internal_notes,
                    'allergies' => $n->allergies ?? [],
                    'status' => $n->status,
                    'signed_at' => $n->signed_at ? (is_string($n->signed_at) ? $n->signed_at : $n->signed_at->format('Y-m-d H:i')) : null,
                ];
            });

        $medicalNotes = $patient->medical_history ?? ($patient->customer ? $patient->customer->medical_history : '');
        $internalStaffNote = $latestAppointment ? ($latestAppointment->internal_notes ?? '') : '';
        $patientNote = $latestAppointment ? ($latestAppointment->notes ?? '') : '';

        $contextClinician = 'Dr. Marcus Thorne';
        if ($latestAppointment && $latestAppointment->staff) {
            $contextClinician = trim($latestAppointment->staff->first_name . ' ' . $latestAppointment->staff->last_name);
        }

        return response()->json([
            'status' => 'success',
            'patientContext' => [
                'id' => $patient->patient_code ?? ('PAT-' . str_pad($patient->id, 4, '0', STR_PAD_LEFT)),
                'rawId' => $patient->id,
                'name' => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
                'dob' => $patient->dob ? (is_string($patient->dob) ? $patient->dob : $patient->dob->format('Y-m-d')) : '',
                'gender' => $patient->gender ?? 'Other',
                'service' => $latestAppointment && $latestAppointment->service ? ($latestAppointment->service->title ?? $latestAppointment->service->service_name) : 'Clinical Consultation',
                'time' => $latestAppointment ? ($latestAppointment->start_time ? date('g:i A', strtotime($latestAppointment->start_time)) : '') : '',
                'clinician' => $contextClinician,
                'medicalNotes' => $medicalNotes,
                'internalStaffNote' => $internalStaffNote,
                'patientNote' => $patientNote,
                'allergies' => $allergies,
            ],
            'notes' => $notes
        ]);
    }

    public function storeForPatient(Request $request, $patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        $validated = $request->validate([
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'internalNotes' => 'nullable|string',
            'medicalNotes' => 'nullable|string',
            'allergies' => 'nullable|array',
            'status' => 'nullable|string',
            'appointment_id' => 'nullable',
            'clinician_name' => 'nullable|string',
        ]);

        $staffId = null;
        if (auth()->check()) {
            $user = auth()->user();
            if (isset($user->staff_id) && $user->staff_id) {
                $staffId = $user->staff_id;
            } else {
                $staff = \App\Models\Staff::where('user_id', $user->id)->orWhere('email', $user->email)->first();
                if ($staff) {
                    $staffId = $staff->id;
                }
            }
        }

        if (!$staffId) {
            $clinicianStaff = \App\Models\Staff::where('role', 'clinician')->first();
            if ($clinicianStaff) {
                $staffId = $clinicianStaff->id;
            }
        }

        $clinicianNameInput = $validated['clinician_name'] ?? $request->input('author') ?? null;
        if (empty($clinicianNameInput) && $staffId) {
            $stf = \App\Models\Staff::find($staffId);
            if ($stf) {
                $clinicianNameInput = trim($stf->first_name . ' ' . $stf->last_name);
            }
        }
        if (empty($clinicianNameInput)) {
            $clinicianNameInput = 'Dr. Marcus Thorne';
        }

        $aptId = null;
        if (!empty($validated['appointment_id']) && $validated['appointment_id'] !== 'NEW') {
            $aptRaw = intval(preg_replace('/[^0-9]/', '', $validated['appointment_id']));
            $aptObj = Appointment::where('id', $validated['appointment_id'])->orWhere('id', $aptRaw)->orWhere('appointment_code', $validated['appointment_id'])->first();
            if ($aptObj) {
                $aptId = $aptObj->id;
            }
        }

        $isFinalizing = ($validated['status'] ?? 'Finalized') === 'Finalized';

        $note = ClinicalNote::create([
            'patient_id' => $patient->id,
            'appointment_id' => $aptId,
            'clinician_id' => $staffId,
            'clinician_name' => $clinicianNameInput,
            'subjective' => $validated['subjective'] ?? null,
            'objective' => $validated['objective'] ?? null,
            'assessment' => $validated['assessment'] ?? null,
            'plan' => $validated['plan'] ?? null,
            'internal_notes' => $validated['internalNotes'] ?? null,
            'allergies' => $validated['allergies'] ?? [],
            'status' => $isFinalizing ? 'Finalized' : 'Draft',
            'signed_at' => $isFinalizing ? Carbon::now() : null,
        ]);

        // Update patient's allergies & medical_history if provided
        $updateData = [];
        if (isset($validated['allergies'])) {
            $updateData['allergies'] = $validated['allergies'];
        }
        if (isset($validated['medicalNotes']) && !empty($validated['medicalNotes'])) {
            $updateData['medical_history'] = $validated['medicalNotes'];
        }
        if (!empty($updateData)) {
            $patient->update($updateData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Clinical note signed and locked successfully',
            'data' => $note
        ], 201);
    }

    public function storeOrUpdate(Request $request, $appointmentId)
    {
        if ($appointmentId === 'NEW' || !is_numeric($appointmentId)) {
            $patientId = $request->input('patient_id') || $request->input('patientId');
            if ($patientId) {
                return $this->storeForPatient($request, $patientId);
            }
        }

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
            'clinician_name' => 'nullable|string',
        ]);

        $clinicianNameInput = $validated['clinician_name'] ?? $request->input('author') ?? null;
        if (empty($clinicianNameInput) && $appointment->staff_id) {
            $stf = \App\Models\Staff::find($appointment->staff_id);
            if ($stf) {
                $clinicianNameInput = trim($stf->first_name . ' ' . $stf->last_name);
            }
        }
        if (empty($clinicianNameInput)) {
            $clinicianNameInput = 'Dr. Marcus Thorne';
        }

        $isFinalizing = ($validated['status'] ?? '') === 'Finalized';

        $note = ClinicalNote::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'clinician_id' => $appointment->staff_id,
                'clinician_name' => $clinicianNameInput,
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

        if (isset($validated['allergies']) && $appointment->patient) {
            $appointment->patient->update(['allergies' => $validated['allergies']]);
        }

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

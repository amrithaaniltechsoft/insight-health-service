<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with(['appointments.service', 'clinicalNotes']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('patient_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('created_at', 'desc')->get()->map(function($p) {
            return [
                'id' => $p->patient_code,
                'rawId' => $p->id,
                'name' => $p->first_name . ' ' . $p->last_name,
                'firstName' => $p->first_name,
                'lastName' => $p->last_name,
                'dob' => $p->dob ? $p->dob->format('Y-m-d') : '',
                'gender' => $p->gender,
                'email' => $p->email,
                'phone' => $p->phone,
                'address' => $p->address,
                'status' => $p->status,
                'allergies' => $p->allergies ?? [],
                'medicalHistory' => $p->medical_history ?? [],
                'history' => [
                    'past' => $p->appointments->map(function($apt) {
                        return [
                            'id' => $apt->appointment_code,
                            'date' => $apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : '',
                            'time' => $apt->start_time,
                            'services' => [$apt->service ? $apt->service->title : 'Health Scan'],
                            'status' => $apt->status,
                        ];
                    })
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $patients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $code = 'PAT-' . rand(1000, 9999);

        $patient = Patient::create([
            'patient_code' => $code,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? 'Other',
            'address' => $validated['address'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Patient created successfully',
            'data' => $patient
        ], 201);
    }

    public function show($id)
    {
        $patient = Patient::with(['appointments.service', 'clinicalNotes'])
            ->where('id', $id)
            ->orWhere('patient_code', $id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $patient
        ]);
    }

    public function update(Request $request, $id)
    {
        $patient = Patient::where('id', $id)
            ->orWhere('patient_code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'allergies' => 'nullable|array',
            'medical_history' => 'nullable|array',
        ]);

        $patient->update(array_filter($validated));

        return response()->json([
            'status' => 'success',
            'message' => 'Patient updated successfully',
            'data' => $patient
        ]);
    }
}

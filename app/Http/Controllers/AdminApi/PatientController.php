<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function checkEmail(Request $request)
    {
        $email = trim($request->query('email', ''));

        if (empty($email)) {
            return response()->json(['status' => 'error', 'message' => 'Email is required'], 422);
        }

        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return response()->json([
                'status' => 'success',
                'exists' => false,
                'customer' => null,
                'patients' => []
            ]);
        }

        // Get all patients linked to this customer
        $patients = Patient::where('customer_id', $customer->id)->get();

        // If customer exists but has no records in patients table yet, auto-create a primary Patient entry for them
        if ($patients->isEmpty()) {
            $patientCode = Patient::generateNextPatientCode();
            $primaryPatient = Patient::create([
                'patient_code' => $patientCode,
                'customer_id' => $customer->id,
                'first_name' => $customer->first_name ?? 'Customer',
                'last_name' => $customer->last_name ?? '',
                'dob' => $customer->dob,
                'gender' => $customer->gender ?? 'Other',
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => trim(($customer->address_line_1 ?? '') . ' ' . ($customer->city ?? '')),
                'status' => 'active',
            ]);
            $patients = collect([$primaryPatient]);
        }

        return response()->json([
            'status' => 'success',
            'exists' => true,
            'customer' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code ?? ('CUST-' . $customer->id),
                'name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'email' => $customer->email,
                'phone' => $customer->phone ?? '',
            ],
            'patients' => $patients->map(function($p) {
                return [
                    'id' => $p->id,
                    'patient_code' => $p->patient_code ?? ('PAT-' . $p->id),
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'name' => trim($p->first_name . ' ' . $p->last_name),
                    'dob' => $p->dob ? (is_string($p->dob) ? $p->dob : $p->dob->format('Y-m-d')) : '',
                    'gender' => $p->gender ?? 'Other',
                    'email' => $p->email,
                    'phone' => $p->phone,
                ];
            })
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->query('q', $request->query('search', '')));

        if (empty($q)) {
            $patients = Patient::limit(15)->get();
        } else {
            $patients = Patient::where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('patient_code', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->limit(20)
                ->get();
        }

        // Fallback: search customers if patients query returns empty
        if ($patients->isEmpty() && !empty($q)) {
            $customers = Customer::where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('customer_code', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->limit(20)
                ->get();

            foreach ($customers as $c) {
                $pat = Patient::firstOrCreate(
                    ['customer_id' => $c->id, 'email' => $c->email],
                    [
                        'patient_code' => Patient::generateNextPatientCode(),
                        'first_name' => $c->first_name ?? 'Customer',
                        'last_name' => $c->last_name ?? '',
                        'dob' => $c->dob,
                        'gender' => $c->gender ?? 'Other',
                        'phone' => $c->phone,
                        'status' => 'active',
                    ]
                );
                $patients->push($pat);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $patients->map(function($p) {
                return [
                    'id' => $p->id,
                    'patient_code' => $p->patient_code ?? ('PAT-' . str_pad($p->id, 4, '0', STR_PAD_LEFT)),
                    'name' => trim($p->first_name . ' ' . $p->last_name),
                    'firstName' => $p->first_name,
                    'lastName' => $p->last_name,
                    'email' => $p->email ?? '',
                    'phone' => $p->phone ?? '',
                    'dob' => $p->dob ? (is_string($p->dob) ? $p->dob : $p->dob->format('Y-m-d')) : '',
                    'gender' => $p->gender ?? 'Other',
                ];
            })
        ]);
    }

    public function createUnderCustomer(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'relationship' => 'nullable|string',
        ]);

        $code = Patient::generateNextPatientCode();

        $patient = Patient::create([
            'patient_code' => $code,
            'customer_id' => $validated['customer_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? 'Other',
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Patient created under customer successfully',
            'data' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => trim($patient->first_name . ' ' . $patient->last_name),
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'dob' => $patient->dob ? (is_string($patient->dob) ? $patient->dob : $patient->dob->format('Y-m-d')) : '',
                'gender' => $patient->gender,
            ]
        ], 201);
    }

    public function createWithCustomer(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'title' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
        ]);

        $custCode = Customer::generateNextCustomerCode();

        $customer = Customer::create([
            'customer_code' => $custCode,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? 'Other',
            'title' => $validated['title'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'city' => $validated['city'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'status' => 'active',
        ]);

        $patCode = Patient::generateNextPatientCode();

        $patient = Patient::create([
            'patient_code' => $patCode,
            'customer_id' => $customer->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? 'Other',
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer and Patient created successfully',
            'data' => [
                'customer_id' => $customer->id,
                'patient_id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => trim($patient->first_name . ' ' . $patient->last_name),
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'dob' => $patient->dob ? (is_string($patient->dob) ? $patient->dob : $patient->dob->format('Y-m-d')) : '',
                'gender' => $patient->gender,
            ]
        ], 201);
    }

    public function patientsIndex(Request $request)
    {
        $query = Patient::with(['customer', 'appointments.service']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('patient_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('created_at', 'desc')->get()->map(function($p) {
            $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
            if (empty($fullName) && !empty($p->name)) $fullName = $p->name;
            if (empty($fullName)) $fullName = 'Patient #' . $p->id;

            $customerName = 'N/A';
            if ($p->customer) {
                $cName = trim(($p->customer->first_name ?? '') . ' ' . ($p->customer->last_name ?? ''));
                $customerName = !empty($cName) ? $cName : ($p->customer->name ?? 'Customer #' . $p->customer->id);
            }

            return [
                'id' => $p->patient_code ?? ('PAT-' . str_pad($p->id, 4, '0', STR_PAD_LEFT)),
                'rawId' => $p->id,
                'name' => $fullName,
                'firstName' => $p->first_name ?? $fullName,
                'lastName' => $p->last_name ?? '',
                'dob' => $p->dob ? (is_string($p->dob) ? $p->dob : $p->dob->format('Y-m-d')) : '',
                'gender' => $p->gender ?? 'Other',
                'blood_group' => $p->blood_group ?? '',
                'bloodGroup' => $p->blood_group ?? '',
                'allergies' => $p->allergies ?? [],
                'email' => $p->email ?? ($p->customer ? $p->customer->email : ''),
                'phone' => $p->phone ?? ($p->customer ? $p->customer->phone : 'N/A'),
                'customerName' => $customerName,
                'customerId' => $p->customer_id,
                'address' => $p->address ?? '',
                'status' => $p->status ?? 'active',
                'history' => [
                    'past' => ($p->appointments ?? collect())->map(function($apt) {
                        return [
                            'id' => $apt->appointment_code,
                            'date' => $apt->appointment_date ? (is_string($apt->appointment_date) ? $apt->appointment_date : $apt->appointment_date->format('Y-m-d')) : '',
                            'time' => $apt->start_time,
                            'services' => [$apt->service ? ($apt->service->title ?? $apt->service->service_name) : 'Health Scan'],
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

    public function customersIndex(Request $request)
    {
        $query = Customer::with(['patients']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->get()->map(function($c) {
            $fullName = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = 'Customer #' . $c->id;
            }

            $code = $c->customer_code ?? ('CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT));

            return [
                'id' => $code,
                'rawId' => $c->id,
                'name' => $fullName,
                'firstName' => $c->first_name ?? $fullName,
                'lastName' => $c->last_name ?? '',
                'dob' => $c->dob ? (is_string($c->dob) ? $c->dob : $c->dob->format('Y-m-d')) : '',
                'gender' => $c->gender ?? 'Other',
                'email' => $c->email ?? '',
                'phone' => $c->phone ?? 'N/A',
                'title' => $c->title ?? '',
                'address' => trim(($c->address_line_1 ?? '') . ' ' . ($c->city ?? '')),
                'patientsCount' => $c->patients ? $c->patients->count() : 0,
                'patientsList' => ($c->patients ?? collect())->map(fn($p) => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')))->filter()->toArray(),
                'status' => $c->status ?? 'active',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    public function index(Request $request)
    {
        return $this->customersIndex($request);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'title' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'suburb' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'country' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $code = Customer::generateNextCustomerCode();

        $customer = Customer::create([
            'customer_code' => $code,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? 'Other',
            'title' => $validated['title'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'suburb' => $validated['suburb'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'country' => $validated['country'] ?? null,
            'medical_history' => $validated['medical_history'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    public function showPatient($id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $patient = Patient::with([
            'customer',
            'appointments.service',
            'appointments.staff',
            'clinicalNotes',
            'medicalFiles.uploader'
        ])
        ->where('id', $id)
        ->orWhere('id', $rawId)
        ->orWhere('patient_code', $id)
        ->firstOrFail();

        $fullName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = 'Patient #' . $patient->id;
        }

        $code = $patient->patient_code ?? ('PAT-' . str_pad($patient->id, 4, '0', STR_PAD_LEFT));

        $customerData = null;
        if ($patient->customer) {
            $c = $patient->customer;
            $cName = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
            $customerData = [
                'id' => $c->customer_code ?? ('CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT)),
                'rawId' => $c->id,
                'name' => !empty($cName) ? $cName : 'Customer #' . $c->id,
                'email' => $c->email,
                'phone' => $c->phone ?? '',
            ];
        }

        $appointments = ($patient->appointments ?? collect())->map(function($apt) {
            $serviceTitles = [];
            if (is_array($apt->service_ids) && count($apt->service_ids) > 0) {
                $services = \App\Models\Service::whereIn('id', $apt->service_ids)->get();
                $serviceTitles = $services->map(fn($s) => $s->title ?? $s->service_name)->filter()->toArray();
            }
            if (count($serviceTitles) === 0 && $apt->service) {
                $serviceTitles[] = $apt->service->title ?? $apt->service->service_name;
            }
            $serviceDisplay = count($serviceTitles) > 0 ? implode(" + ", $serviceTitles) : 'Health Scan';

            return [
                'id' => $apt->appointment_code,
                'rawId' => $apt->id,
                'date' => $apt->appointment_date ? (is_string($apt->appointment_date) ? $apt->appointment_date : $apt->appointment_date->format('Y-m-d')) : '',
                'time' => $apt->start_time ? date('g:i A', strtotime($apt->start_time)) : '',
                'service' => $serviceDisplay,
                'status' => $apt->status,
                'paymentStatus' => $apt->payment_status,
                'clinician' => $apt->staff ? trim($apt->staff->first_name . ' ' . $apt->staff->last_name) : 'Unassigned',
                'notes' => $apt->notes,
            ];
        });

        $clinicalNotes = ($patient->clinicalNotes ?? collect())->map(function($note) {
            $clinicianName = $note->clinician_name;
            if (empty($clinicianName) && $note->clinician) {
                $clinicianName = trim($note->clinician->first_name . ' ' . $note->clinician->last_name);
            }
            if (empty($clinicianName) && $note->appointment && $note->appointment->staff) {
                $clinicianName = trim($note->appointment->staff->first_name . ' ' . $note->appointment->staff->last_name);
            }
            if (empty($clinicianName)) {
                $clinicianName = 'Dr. Marcus Thorne';
            }

            $serviceName = 'Clinical Consultation';
            if ($note->appointment && $note->appointment->service) {
                $serviceName = $note->appointment->service->title ?? $note->appointment->service->service_name;
            }

            return [
                'id' => $note->id,
                'appointment_id' => $note->appointment_id,
                'created_at' => $note->created_at ? $note->created_at->format('Y-m-d') : date('Y-m-d'),
                'clinician_name' => $clinicianName,
                'service' => $serviceName,
                'subjective' => $note->subjective,
                'objective' => $note->objective,
                'assessment' => $note->assessment,
                'plan' => $note->plan,
                'internal_notes' => $note->internal_notes,
                'allergies' => $note->allergies ?? [],
                'status' => $note->status,
                'signed_at' => $note->signed_at ? (is_string($note->signed_at) ? $note->signed_at : $note->signed_at->format('Y-m-d H:i')) : '',
            ];
        });

        $medicalFiles = ($patient->medicalFiles ?? collect())->map(function($f) {
            return [
                'id' => $f->id,
                'title' => $f->title,
                'file_name' => $f->file_name,
                'file_type' => $f->file_type,
                'mime_type' => $f->mime_type,
                'file_size' => $f->file_size,
                'notes' => $f->notes,
                'url' => asset('storage/' . $f->file_path),
                'uploaded_by' => $f->uploader ? trim($f->uploader->first_name . ' ' . $f->uploader->last_name) : 'Staff Member',
                'created_at' => $f->created_at ? $f->created_at->format('Y-m-d H:i') : '',
            ];
        });

        $formatted = [
            'id' => $code,
            'rawId' => $patient->id,
            'patient_code' => $code,
            'name' => $fullName,
            'first_name' => $patient->first_name ?? '',
            'last_name' => $patient->last_name ?? '',
            'dob' => $patient->dob ? (is_string($patient->dob) ? $patient->dob : $patient->dob->format('Y-m-d')) : '',
            'gender' => $patient->gender ?? 'Other',
            'blood_group' => $patient->blood_group ?? '',
            'bloodGroup' => $patient->blood_group ?? '',
            'email' => $patient->email ?? ($patient->customer ? $patient->customer->email : ''),
            'phone' => $patient->phone ?? ($patient->customer ? $patient->customer->phone : ''),
            'address' => $patient->address ?? '',
            'medical_history' => $patient->medical_history ?? '',
            'allergies' => $patient->allergies ?? [],
            'emergency_contact' => $patient->emergency_contact ?? null,
            'status' => $patient->status ?? 'active',
            'customer' => $customerData,
            'appointments' => $appointments,
            'clinicalNotes' => $clinicalNotes,
            'medicalFiles' => $medicalFiles,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    public function updatePatient(Request $request, $id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $patient = Patient::where('id', $id)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'allergies' => 'nullable',
            'address' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if ($request->has('allergies')) {
            $rawAllergies = $request->input('allergies');
            if (is_string($rawAllergies)) {
                $validated['allergies'] = array_values(array_filter(array_map('trim', explode(',', $rawAllergies))));
            }
        }

        $data = array_filter($validated, function($v) {
            return !is_null($v);
        });

        if (!empty($data)) {
            $patient->update($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Patient profile updated successfully',
            'data' => $patient
        ]);
    }

    public function showCustomer($id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $customer = Customer::with(['patients'])
            ->where('id', $id)
            ->orWhere('id', $rawId)
            ->orWhere('customer_code', $id)
            ->firstOrFail();

        $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = 'Customer #' . $customer->id;
        }

        $code = $customer->customer_code ?? ('CUST-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT));

        $registeredPatients = ($customer->patients ?? collect())->map(function($p) {
            $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
            return [
                'id' => $p->patient_code ?? ('PAT-' . str_pad($p->id, 4, '0', STR_PAD_LEFT)),
                'rawId' => $p->id,
                'patient_code' => $p->patient_code ?? ('PAT-' . str_pad($p->id, 4, '0', STR_PAD_LEFT)),
                'name' => !empty($pName) ? $pName : 'Patient #' . $p->id,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'dob' => $p->dob ? (is_string($p->dob) ? $p->dob : $p->dob->format('Y-m-d')) : '',
                'gender' => $p->gender ?? 'Other',
                'email' => $p->email,
                'phone' => $p->phone,
                'status' => $p->status ?? 'active',
            ];
        });

        $formatted = [
            'id' => $code,
            'rawId' => $customer->id,
            'customer_code' => $code,
            'name' => $fullName,
            'first_name' => $customer->first_name ?? $fullName,
            'last_name' => $customer->last_name ?? '',
            'dob' => $customer->dob ? (is_string($customer->dob) ? $customer->dob : $customer->dob->format('Y-m-d')) : '',
            'gender' => $customer->gender ?? 'Other',
            'email' => $customer->email,
            'phone' => $customer->phone ?? 'N/A',
            'title' => $customer->title ?? '',
            'address_line_1' => $customer->address_line_1 ?? '',
            'address_line_2' => $customer->address_line_2 ?? '',
            'suburb' => $customer->suburb ?? '',
            'city' => $customer->city ?? '',
            'state' => $customer->state ?? '',
            'zip_code' => $customer->zip_code ?? '',
            'country' => $customer->country ?? '',
            'status' => $customer->status ?? 'active',
            'patients' => $registeredPatients,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $customer = Customer::where('id', $id)
            ->orWhere('id', $rawId)
            ->orWhere('customer_code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'title' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'suburb' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'country' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $data = array_filter($validated, function($v) {
            return !is_null($v);
        });

        if (!empty($data)) {
            $customer->update($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Customer profile updated successfully',
            'data' => $customer
        ]);
    }
}

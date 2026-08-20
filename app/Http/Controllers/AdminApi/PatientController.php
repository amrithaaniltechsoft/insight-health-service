<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['appointments.service', 'clinicalNotes']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->get()->map(function($c) {
            $fullName = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $c->name ?? 'Customer';
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
                'email' => $c->email,
                'phone' => $c->phone ?? 'N/A',
                'title' => $c->title ?? '',
                'address_line_1' => $c->address_line_1 ?? '',
                'address_line_2' => $c->address_line_2 ?? '',
                'suburb' => $c->suburb ?? '',
                'city' => $c->city ?? '',
                'state' => $c->state ?? '',
                'zip_code' => $c->zip_code ?? '',
                'country' => $c->country ?? '',
                'medical_history' => $c->medical_history ?? '',
                'status' => $c->status ?? 'active',
                'allergies' => [],
                'medicalHistory' => $c->medical_history ? (is_array($c->medical_history) ? $c->medical_history : explode(',', $c->medical_history)) : [],
                'history' => [
                    'past' => ($c->appointments ?? collect())->map(function($apt) {
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
            'data' => $customers
        ]);
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

        $code = 'CUST-' . rand(1000, 9999);

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

    public function show($id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $customer = Customer::with(['appointments.service', 'clinicalNotes'])
            ->where('id', $id)
            ->orWhere('id', $rawId)
            ->orWhere('customer_code', $id)
            ->firstOrFail();

        $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $customer->name ?? 'Customer';
        }

        $code = $customer->customer_code ?? ('CUST-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT));

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
            'medical_history' => $customer->medical_history ?? '',
            'status' => $customer->status ?? 'active',
            'medical_history' => $customer->medical_history ?? '',
            'appointments' => $customer->appointments,
            'clinicalNotes' => $customer->clinicalNotes,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    public function update(Request $request, $id)
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
            'medical_history' => 'nullable|string',
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

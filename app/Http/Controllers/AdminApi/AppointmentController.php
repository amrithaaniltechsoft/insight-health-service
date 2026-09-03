<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'service', 'staff', 'clinic']);

        // Search query
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('appointment_code', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('patient_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('appointment_date', $request->date);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        if ($request->has('clinic_id') && !empty($request->clinic_id)) {
            $query->where('clinic_id', $request->clinic_id);
        }

        if ($request->has('staff_id') && !empty($request->staff_id)) {
            $query->where('staff_id', $request->staff_id);
        }

        $perPage = intval($request->query('per_page', 15));
        if ($perPage <= 0) $perPage = 15;

        $paginated = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate($perPage);

        $appointments = collect($paginated->items())->map(function ($apt) {
            $patientName = 'N/A';
            $patientIdStr = '';
            $patientEmail = '';
            $patientPhone = '';

            if ($apt->patient) {
                $p = $apt->patient;
                $name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                if (empty($name) && !empty($p->name)) $name = $p->name;
                if (empty($name) && $p->customer) {
                    $name = trim(($p->customer->first_name ?? '') . ' ' . ($p->customer->last_name ?? ''));
                    if (empty($name) && !empty($p->customer->name)) $name = $p->customer->name;
                }
                $patientName = !empty($name) ? $name : ('Patient #' . $p->id);
                $patientIdStr = $p->patient_code ?? ('PAT-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
                $patientEmail = $p->email ?? ($p->customer ? $p->customer->email : '');
                $patientPhone = $p->phone ?? ($p->customer ? $p->customer->phone : '');
            } else if ($apt->patient_id) {
                $c = Customer::find($apt->patient_id);
                if ($c) {
                    $name = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                    if (empty($name) && !empty($c->name)) $name = $c->name;
                    $patientName = !empty($name) ? $name : ('Customer #' . $c->id);
                    $patientIdStr = $c->customer_code ?? ('CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT));
                    $patientEmail = $c->email ?? '';
                    $patientPhone = $c->phone ?? '';
                }
            }

            $serviceTitles = [];
            if (is_array($apt->service_ids) && count($apt->service_ids) > 0) {
                $services = Service::whereIn('id', $apt->service_ids)->get();
                $serviceTitles = $services->map(fn($s) => $s->title ?? $s->service_name)->toArray();
            }
            if (count($serviceTitles) === 0 && $apt->service) {
                $serviceTitles[] = $apt->service->title ?? $apt->service->service_name;
            }
            $serviceDisplay = count($serviceTitles) > 0 ? implode(" + ", $serviceTitles) : 'Health Scan';

            return [
                'id' => $apt->appointment_code,
                'rawId' => $apt->id,
                'patientName' => $patientName,
                'patientId' => $patientIdStr,
                'patientEmail' => $patientEmail,
                'patientPhone' => $patientPhone,
                'service' => $serviceDisplay,
                'serviceId' => $apt->service_id,
                'serviceIds' => $apt->service_ids ?? ($apt->service_id ? [$apt->service_id] : []),
                'date' => $apt->appointment_date ? (is_string($apt->appointment_date) ? $apt->appointment_date : $apt->appointment_date->format('Y-m-d')) : '',
                'time' => $apt->start_time ? date('g:i A', strtotime($apt->start_time)) : '',
                'status' => $apt->status,
                'paymentStatus' => $apt->payment_status,
                'clinician' => $apt->staff ? trim($apt->staff->first_name . ' ' . $apt->staff->last_name) : 'Unassigned',
                'staffId' => $apt->staff_id,
                'notes' => $apt->notes,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $appointments,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    public function bookedSlots(Request $request)
    {
        $date = $request->query('date');
        $staffIdParam = $request->query('staff_id');

        if (!$date) {
            return response()->json(['status' => 'success', 'bookedSlots' => []]);
        }

        $query = Appointment::whereDate('appointment_date', $date)
            ->where('status', '!=', 'Cancelled');

        if ($staffIdParam) {
            $staff = Staff::where('id', $staffIdParam)
                ->orWhere('staff_code', $staffIdParam)
                ->orWhere(\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$staffIdParam}%")
                ->orWhere('first_name', 'like', "%{$staffIdParam}%")
                ->first();

            if ($staff) {
                $query->where('staff_id', $staff->id);
            }
        }

        $bookedSlots = $query->pluck('start_time')->flatMap(function($t) {
            if (!$t) return [];
            $ts = strtotime($t);
            if (!$ts) return [$t];
            return [
                $t,
                date('h:i A', $ts),
                date('g:i A', $ts),
                date('H:i:s', $ts),
                date('H:i', $ts),
            ];
        })->unique()->values()->toArray();

        return response()->json([
            'status' => 'success',
            'bookedSlots' => $bookedSlots
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required',
            'service_id' => 'nullable',
            'service_ids' => 'nullable|array',
            'appointment_date' => 'required|date',
            'start_time' => 'required',
            'clinic_id' => 'nullable',
            'staff_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        // Find patient in Patient table first
        $patient = Patient::where('id', $validated['patient_id'])
            ->orWhere('patient_code', $validated['patient_id'])
            ->first();

        // Fallback: search Customer table if not found in Patient table
        if (!$patient) {
            $customer = Customer::where('id', $validated['patient_id'])
                ->orWhere('customer_code', $validated['patient_id'])
                ->first();

            if ($customer) {
                $patient = Patient::firstOrCreate(
                    ['customer_id' => $customer->id, 'email' => $customer->email],
                    [
                        'patient_code' => 'PAT-' . rand(1000, 9999),
                        'first_name' => $customer->first_name ?? 'Customer',
                        'last_name' => $customer->last_name ?? '',
                        'dob' => $customer->dob,
                        'gender' => $customer->gender ?? 'Other',
                        'phone' => $customer->phone,
                        'status' => 'active',
                    ]
                );
            }
        }

        if (!$patient) {
            return response()->json(['message' => 'Patient record not found.'], 404);
        }

        // Multi-service resolution
        $rawServiceIds = $request->input('service_ids', []);
        if (!is_array($rawServiceIds) && !empty($rawServiceIds)) {
            $rawServiceIds = [$rawServiceIds];
        }
        if (empty($rawServiceIds) && !empty($validated['service_id'])) {
            $rawServiceIds = [$validated['service_id']];
        }

        $serviceIds = array_values(array_filter(array_map(function($id) {
            return intval(preg_replace('/[^0-9]/', '', (string)$id));
        }, (array)$rawServiceIds)));

        $serviceNames = [];
        $service = null;

        if (count($serviceIds) > 0) {
            $services = Service::whereIn('id', $serviceIds)->get();
            if ($services->count() > 0) {
                $service = $services->first();
                $serviceNames = $services->map(fn($s) => $s->title ?? $s->service_name)->filter()->toArray();
            }
        }

        // Fallback Single Service ID resolution
        if (!$service && !empty($validated['service_id'])) {
            $service = Service::where('title', 'like', "%{$validated['service_id']}%")
                ->orWhere('service_name', 'like', "%{$validated['service_id']}%")
                ->first();
        }

        // Final fallback to prevent foreign key failure
        if (!$service) {
            $service = Service::first();
        }

        $serviceId = $service ? $service->id : null;
        if ($serviceId && !in_array($serviceId, $serviceIds)) {
            $serviceIds[] = $serviceId;
        }
        if ($service && count($serviceNames) === 0) {
            $serviceNames[] = $service->title ?? $service->service_name;
        }

        // Combine notes with multi-service summary
        $notesText = $validated['notes'] ?? '';
        if (count($serviceNames) > 1) {
            $summary = "Selected Services: " . implode(", ", $serviceNames);
            $notesText = !empty($notesText) ? ($summary . " | " . $notesText) : $summary;
        }

        // Robust Staff ID resolution
        $staffId = null;
        if (!empty($validated['staff_id'])) {
            $assignedStaff = Staff::where('id', $validated['staff_id'])
                ->orWhere('staff_code', $validated['staff_id'])
                ->orWhere(\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$validated['staff_id']}%")
                ->orWhere('first_name', 'like', "%{$validated['staff_id']}%")
                ->first();

            if ($assignedStaff) {
                if ($assignedStaff->status === 'inactive' || $assignedStaff->status === 'revoked') {
                    return response()->json(['message' => 'Cannot assign appointment: Staff account access is revoked.'], 422);
                }
                if ($assignedStaff->availability === 'unavailable') {
                    return response()->json(['message' => 'Cannot assign appointment: Clinician is currently marked as Unavailable.'], 422);
                }
                $staffId = $assignedStaff->id;
            }
        }

        // Format start_time to HH:i:s for MySQL TIME column compatibility
        $startTimeFormatted = date('H:i:s', strtotime($validated['start_time']));

        $code = 'APT-' . rand(1000, 9999);

        $appointment = Appointment::create([
            'appointment_code' => $code,
            'patient_id' => $patient->id,
            'service_id' => $serviceId,
            'service_ids' => $serviceIds,
            'clinic_id' => $validated['clinic_id'] ?? null,
            'staff_id' => $staffId,
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $startTimeFormatted,
            'status' => 'Scheduled',
            'payment_status' => 'Pending',
            'notes' => $notesText,
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
            'service_id' => 'nullable',
            'service_ids' => 'nullable|array',
            'appointment_date' => 'nullable|date',
            'start_time' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $updateData = [];

        if (!empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (!empty($validated['payment_status'])) {
            $updateData['payment_status'] = $validated['payment_status'];
        }

        if (!empty($validated['appointment_date'])) {
            $updateData['appointment_date'] = $validated['appointment_date'];
        }

        if (!empty($validated['start_time'])) {
            $updateData['start_time'] = date('H:i:s', strtotime($validated['start_time']));
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        // Multi-service update
        if (array_key_exists('service_ids', $validated) && is_array($validated['service_ids']) && count($validated['service_ids']) > 0) {
            $numericIds = array_values(array_filter(array_map(function($sId) {
                return intval(preg_replace('/[^0-9]/', '', (string)$sId));
            }, $validated['service_ids'])));

            $services = Service::whereIn('id', $numericIds)->get();
            if ($services->count() > 0) {
                $updateData['service_id'] = $services->first()->id;
                $updateData['service_ids'] = $numericIds;
                $names = $services->map(fn($s) => $s->title ?? $s->service_name)->filter()->toArray();
                $existingNotes = $updateData['notes'] ?? $appointment->notes ?? '';
                // remove previous "Selected Services: ..." tag if present
                $existingNotes = preg_replace('/Selected Services: [^|]+\s*\|?\s*/', '', $existingNotes);
                $summary = "Selected Services: " . implode(", ", $names);
                $updateData['notes'] = !empty(trim($existingNotes)) ? ($summary . " | " . trim($existingNotes)) : $summary;
            }
        } else if (!empty($validated['service_id'])) {
            $service = Service::find($validated['service_id']);
            if (!$service) {
                $rawId = intval(preg_replace('/[^0-9]/', '', $validated['service_id']));
                $service = Service::where('id', $rawId)
                    ->orWhere('title', 'like', "%{$validated['service_id']}%")
                    ->orWhere('service_name', 'like', "%{$validated['service_id']}%")
                    ->first();
            }
            if ($service) {
                $updateData['service_id'] = $service->id;
                $updateData['service_ids'] = [$service->id];
            }
        }

        if (!empty($validated['staff_id'])) {
            $staff = Staff::where('id', $validated['staff_id'])
                ->orWhere('staff_code', $validated['staff_id'])
                ->orWhere(\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$validated['staff_id']}%")
                ->orWhere('first_name', 'like', "%{$validated['staff_id']}%")
                ->first();

            if ($staff) {
                if ($staff->status === 'inactive' || $staff->status === 'revoked') {
                    return response()->json(['message' => 'Cannot assign appointment: Staff account access is revoked.'], 422);
                }
                if ($staff->availability === 'unavailable') {
                    return response()->json(['message' => 'Cannot assign appointment: Clinician is currently marked as Unavailable.'], 422);
                }
                $updateData['staff_id'] = $staff->id;
            }
        }

        if (!empty($updateData)) {
            $appointment->update($updateData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment updated successfully',
            'data' => $appointment->fresh(['patient', 'service', 'staff'])
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

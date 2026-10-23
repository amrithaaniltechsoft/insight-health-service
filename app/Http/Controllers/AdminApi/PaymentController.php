<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['patient', 'appointment.service']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_code', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = intval($request->query('per_page', 50));
        if ($perPage <= 0 || $perPage > 200) $perPage = 50;

        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $payments = collect($paginated->items())->map(function($pay) {
            return [
                'id' => $pay->invoice_number,
                'rawId' => $pay->id,
                'patientName' => $pay->patient ? trim($pay->patient->first_name . ' ' . $pay->patient->last_name) : 'Patient',
                'patientId' => $pay->patient ? $pay->patient->patient_code : '',
                'amount' => (float) $pay->total_amount,
                'paid' => (float) $pay->amount_paid,
                'method' => $pay->payment_method,
                'status' => $pay->status,
                'date' => $pay->created_at ? $pay->created_at->format('Y-m-d') : '',
                'service' => ($pay->appointment && $pay->appointment->service) ? ($pay->appointment->service->title ?? $pay->appointment->service->service_name) : 'Health Service',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $payments,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required',
            'total_amount' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'appointment_id' => 'nullable',
        ]);

        $patient = Patient::where('id', $validated['patient_id'])
            ->orWhere('patient_code', $validated['patient_id'])
            ->firstOrFail();

        $invoiceNum = 'INV-' . date('Y') . '-' . rand(1000, 9999);

        $payment = Payment::create([
            'invoice_number' => $invoiceNum,
            'patient_id' => $patient->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'subtotal' => $validated['total_amount'],
            'total_amount' => $validated['total_amount'],
            'amount_paid' => $validated['amount_paid'],
            'payment_method' => $validated['payment_method'] ?? 'Card',
            'status' => ($validated['amount_paid'] >= $validated['total_amount']) ? 'Paid' : 'Partial',
        ]);

        if ($validated['appointment_id']) {
            Appointment::where('id', $validated['appointment_id'])
                ->orWhere('appointment_code', $validated['appointment_id'])
                ->update(['payment_status' => $payment->status]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment recorded successfully',
            'data' => $payment
        ], 201);
    }
}

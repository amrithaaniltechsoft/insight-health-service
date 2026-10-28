<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class PaymentController extends Controller
{
    /**
     * Display a listing of financial ledger entries with full payment installment breakdown.
     */
    public function index(Request $request)
    {
        // 1. Ensure table and synchronization exists
        $this->ensureTransactionTableExists();
        $this->syncAppointmentsWithoutPayment();
        $this->syncExistingPaymentTransactions();

        $query = Payment::with(['patient.customer', 'appointment.service', 'transactions']);

        // Filter by Status: Paid (full), Partial, Unpaid (without payment), Refunded
        if ($request->has('status') && !empty($request->status) && $request->status !== 'All') {
            $status = $request->status;
            if ($status === 'Unpaid' || $status === 'Pending') {
                $query->where(function($q) {
                    $q->whereIn('status', ['Pending', 'Unpaid'])
                      ->orWhere('amount_paid', 0);
                });
            } elseif ($status === 'Paid') {
                $query->where('status', 'Paid')
                      ->where('amount_paid', '>', 0);
            } elseif ($status === 'Partial') {
                $query->where('status', 'Partial');
            } elseif ($status === 'Refunded') {
                $query->where('status', 'Refunded');
            }
        }

        // Search by invoice number, patient name, patient code, or appointment ref
        if ($request->has('search') && !empty($request->search)) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_code', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                  })
                  ->orWhereHas('appointment', function($aq) use ($search) {
                      $aq->where('appointment_code', 'like', "%{$search}%");
                  });
            });
        }

        // Calculate overall system financial metrics before pagination
        $allPayments = Payment::all();
        $totalRevenue = (float) $allPayments->where('status', '!=', 'Refunded')->sum('amount_paid');
        $totalOutstanding = (float) $allPayments->map(function($p) {
            return max(0, (float)$p->total_amount - (float)$p->amount_paid);
        })->sum();
        $paidCount = $allPayments->where('status', 'Paid')->count();
        $partialCount = $allPayments->where('status', 'Partial')->count();
        $unpaidCount = $allPayments->filter(fn($p) => in_array($p->status, ['Pending', 'Unpaid']) || $p->amount_paid == 0)->count();
        $refundedCount = $allPayments->where('status', 'Refunded')->count();

        $perPage = intval($request->query('per_page', 50));
        if ($perPage <= 0 || $perPage > 200) $perPage = 50;

        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $payments = collect($paginated->items())->map(function($pay) {
            $apt = $pay->appointment;
            $p = $pay->patient;

            $patientName = 'Patient';
            $patientCode = '';
            if ($p) {
                $patientName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                if (empty($patientName) && $p->customer) {
                    $patientName = trim(($p->customer->first_name ?? '') . ' ' . ($p->customer->last_name ?? ''));
                }
                $patientCode = $p->patient_code ?? ('PAT-' . str_pad($p->id, 4, '0', STR_PAD_LEFT));
            }

            $totalDue = (float) $pay->total_amount;
            $amountPaid = (float) $pay->amount_paid;
            $outstanding = max(0, $totalDue - $amountPaid);

            // Determine display status
            $displayStatus = $pay->status;
            if ($displayStatus === 'Pending' || $amountPaid == 0) {
                $displayStatus = 'Unpaid';
            } elseif ($amountPaid >= $totalDue && $totalDue > 0 && $displayStatus !== 'Refunded') {
                $displayStatus = 'Paid';
            } elseif ($amountPaid > 0 && $amountPaid < $totalDue && $displayStatus !== 'Refunded') {
                $displayStatus = 'Partial';
            }

            // Service title
            $serviceTitle = 'Health Service';
            if ($apt) {
                if (!empty($apt->service_ids) && is_array($apt->service_ids)) {
                    $titles = Service::whereIn('id', $apt->service_ids)->pluck('title')->toArray();
                    if (!empty($titles)) {
                        $serviceTitle = implode(' + ', $titles);
                    }
                }
                if ($serviceTitle === 'Health Service' && $apt->service) {
                    $serviceTitle = $apt->service->title ?? $apt->service->service_name;
                }
            }

            $dateStr = $pay->created_at ? $pay->created_at->format('Y-m-d') : '';
            if (empty($dateStr) && $apt && $apt->appointment_date) {
                $dateStr = is_string($apt->appointment_date) ? $apt->appointment_date : $apt->appointment_date->format('Y-m-d');
            }

            // Map all payment installments / transactions
            $txnList = $pay->transactions->map(function($txn) {
                $dt = $txn->paid_at ?: $txn->created_at;
                return [
                    'id' => $txn->id,
                    'amount' => (float) $txn->amount,
                    'date' => $dt ? $dt->format('Y-m-d H:i') : '',
                    'method' => $txn->payment_method ?: 'Card',
                    'type' => $txn->transaction_type ?: 'Payment',
                    'ref' => $txn->transaction_ref ?: ('TXN-' . $txn->id),
                    'notes' => $txn->notes,
                ];
            })->values()->all();

            // If payment has amount_paid > 0 but no explicit transaction rows, provide synthetic initial transaction
            if (empty($txnList) && $amountPaid > 0) {
                $txnList = [
                    [
                        'id' => 'INIT-' . $pay->id,
                        'amount' => $amountPaid,
                        'date' => $pay->created_at ? $pay->created_at->format('Y-m-d H:i') : date('Y-m-d H:i'),
                        'method' => $pay->payment_method ?: 'Card',
                        'type' => ($displayStatus === 'Partial') ? 'Advance Deposit' : 'Full Payment',
                        'ref' => $pay->transaction_ref ?: $pay->invoice_number,
                        'notes' => ($displayStatus === 'Partial') ? 'Advance payment recorded at booking' : 'Full payment recorded',
                    ]
                ];
            }

            return [
                'id' => $pay->invoice_number,
                'rawId' => $pay->id,
                'appointmentId' => $apt ? $apt->id : null,
                'appointmentRef' => $apt ? ($apt->appointment_code ?? ('APT-' . str_pad($apt->id, 4, '0', STR_PAD_LEFT))) : 'N/A',
                'patientName' => $patientName ?: 'Patient',
                'patientId' => $patientCode,
                'service' => $serviceTitle,
                'date' => $dateStr ?: date('Y-m-d'),
                'amountDue' => $totalDue,
                'amountPaid' => $amountPaid,
                'outstandingBalance' => $outstanding,
                'status' => $displayStatus,
                'method' => $pay->payment_method ?: 'Card',
                'refundHistory' => ($pay->status === 'Refunded') ? $amountPaid : 0,
                'transactions' => $txnList,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $payments,
            'metrics' => [
                'totalRevenue' => $totalRevenue,
                'totalOutstanding' => $totalOutstanding,
                'paidCount' => $paidCount,
                'partialCount' => $partialCount,
                'unpaidCount' => $unpaidCount,
                'refundedCount' => $refundedCount,
            ],
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    /**
     * Record a new transaction or process a payment installment against an existing appointment / invoice.
     */
    public function store(Request $request)
    {
        $this->ensureTransactionTableExists();

        $validated = $request->validate([
            'patient_id' => 'nullable',
            'payment_id' => 'nullable',
            'invoice_number' => 'nullable|string',
            'appointment_id' => 'nullable',
            'total_amount' => 'nullable|numeric',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'transaction_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $additionalPaid = (float) $validated['amount_paid'];
        $method = $validated['payment_method'] ?? 'Card';

        // 1. Check if paying against an existing Payment record
        $payment = null;
        if (!empty($validated['payment_id'])) {
            $payment = Payment::find($validated['payment_id']);
        }
        if (!$payment && !empty($validated['invoice_number'])) {
            $payment = Payment::where('invoice_number', $validated['invoice_number'])->first();
        }
        if (!$payment && !empty($validated['appointment_id'])) {
            $payment = Payment::where('appointment_id', $validated['appointment_id'])->first();
        }

        if ($payment) {
            $newAmountPaid = $payment->amount_paid + $additionalPaid;
            if ($newAmountPaid > $payment->total_amount && $payment->total_amount > 0) {
                $newAmountPaid = $payment->total_amount;
            }

            $newStatus = ($newAmountPaid >= $payment->total_amount && $payment->total_amount > 0)
                ? 'Paid'
                : ($newAmountPaid > 0 ? 'Partial' : 'Pending');

            $payment->update([
                'amount_paid' => $newAmountPaid,
                'payment_method' => $method,
                'status' => $newStatus,
            ]);

            if ($payment->appointment_id) {
                Appointment::where('id', $payment->appointment_id)
                    ->update(['payment_status' => $newStatus]);
            }

            // Record this specific payment installment
            $txnType = $validated['transaction_type'] ?? (($newStatus === 'Paid') ? 'Remaining Balance (At Clinic)' : 'Partial Payment (At Clinic)');
            $txnNotes = $validated['notes'] ?? (($newStatus === 'Paid') ? 'Remaining balance settled at clinic on appointment date' : 'Additional payment at clinic');

            $txnRef = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'amount' => $additionalPaid,
                'payment_method' => $method,
                'transaction_type' => $txnType,
                'transaction_ref' => $txnRef,
                'notes' => $txnNotes,
                'paid_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed and ledger updated successfully',
                'data' => $payment->fresh(['transactions'])
            ]);
        }

        // 2. Otherwise create a brand new payment record
        $patient = null;
        if (!empty($validated['patient_id'])) {
            $patient = Patient::where('id', $validated['patient_id'])
                ->orWhere('patient_code', $validated['patient_id'])
                ->first();
        }

        $appointment = null;
        if (!empty($validated['appointment_id'])) {
            $appointment = Appointment::where('id', $validated['appointment_id'])
                ->orWhere('appointment_code', $validated['appointment_id'])
                ->first();

            if ($appointment && !$patient) {
                $patient = $appointment->patient;
            }
        }

        if (!$patient) {
            $patient = Patient::first();
        }

        $totalAmount = !empty($validated['total_amount']) ? (float)$validated['total_amount'] : $additionalPaid;
        $status = ($additionalPaid >= $totalAmount && $totalAmount > 0) ? 'Paid' : ($additionalPaid > 0 ? 'Partial' : 'Pending');
        $invoiceNum = 'INV-' . date('Y') . '-' . rand(1000, 9999);

        $newPayment = Payment::create([
            'invoice_number' => $invoiceNum,
            'patient_id' => $patient ? $patient->id : 1,
            'appointment_id' => $appointment ? $appointment->id : null,
            'subtotal' => $totalAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => $additionalPaid,
            'payment_method' => $method,
            'status' => $status,
        ]);

        if ($appointment) {
            $appointment->update(['payment_status' => $status]);
        }

        if ($additionalPaid > 0) {
            $txnType = $validated['transaction_type'] ?? (($status === 'Partial') ? 'Advance Deposit' : 'Full Payment');
            PaymentTransaction::create([
                'payment_id' => $newPayment->id,
                'amount' => $additionalPaid,
                'payment_method' => $method,
                'transaction_type' => $txnType,
                'transaction_ref' => 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'notes' => $validated['notes'] ?? (($status === 'Partial') ? 'Advance payment recorded' : 'Full payment recorded'),
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment recorded successfully',
            'data' => $newPayment->fresh(['transactions'])
        ], 201);
    }

    /**
     * Authorize and record a refund for an invoice.
     */
    public function refund(Request $request, $id)
    {
        $this->ensureTransactionTableExists();

        $payment = Payment::where('id', $id)
            ->orWhere('invoice_number', $id)
            ->firstOrFail();

        $payment->update([
            'status' => 'Refunded',
        ]);

        if ($payment->appointment_id) {
            Appointment::where('id', $payment->appointment_id)
                ->update(['payment_status' => 'Pending']);
        }

        // Record negative/refund transaction
        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'amount' => (float) $payment->amount_paid,
            'payment_method' => $payment->payment_method ?: 'Card',
            'transaction_type' => 'Refund Reversal',
            'transaction_ref' => 'REF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'notes' => 'Authorized manual refund reversal',
            'paid_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Refund authorized for invoice {$payment->invoice_number}.",
            'data' => $payment->fresh(['transactions'])
        ]);
    }

    /**
     * Auto-ensure payment_transactions table exists in MySQL database.
     */
    private function ensureTransactionTableExists()
    {
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->string('payment_method')->default('Card');
                $table->string('transaction_type')->default('Payment');
                $table->string('transaction_ref')->nullable();
                $table->string('notes')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Auto-sync any existing appointment that lacks a payment record in the ledger.
     */
    private function syncAppointmentsWithoutPayment()
    {
        $unpaidAppointments = Appointment::doesntHave('payments')
            ->with(['service'])
            ->get();

        foreach ($unpaidAppointments as $apt) {
            $serviceIds = $apt->service_ids ?: ($apt->service_id ? [$apt->service_id] : []);
            $totalPrice = 0.0;
            if (!empty($serviceIds) && is_array($serviceIds)) {
                $totalPrice = (float) Service::whereIn('id', $serviceIds)->sum('price');
            }
            if ($totalPrice <= 0 && $apt->service) {
                $totalPrice = (float) $apt->service->price;
            }
            if ($totalPrice <= 0) {
                $totalPrice = 80.00;
            }

            $status = ($apt->payment_status === 'Paid')
                ? 'Paid'
                : (($apt->payment_status === 'Partial') ? 'Partial' : 'Pending');

            $paid = ($status === 'Paid')
                ? $totalPrice
                : (($status === 'Partial') ? round($totalPrice * 0.5, 2) : 0.00);

            $invoiceNum = 'INV-' . date('Y') . '-' . str_pad($apt->id, 4, '0', STR_PAD_LEFT);
            if (Payment::where('invoice_number', $invoiceNum)->exists()) {
                $invoiceNum = 'INV-' . date('Y') . '-' . rand(1000, 9999);
            }

            $pay = Payment::create([
                'invoice_number' => $invoiceNum,
                'appointment_id' => $apt->id,
                'patient_id' => $apt->patient_id,
                'subtotal' => $totalPrice,
                'total_amount' => $totalPrice,
                'amount_paid' => $paid,
                'payment_method' => $paid > 0 ? 'Card' : 'Unpaid',
                'status' => $status,
            ]);

            if ($paid > 0) {
                PaymentTransaction::create([
                    'payment_id' => $pay->id,
                    'amount' => $paid,
                    'payment_method' => 'Card',
                    'transaction_type' => ($status === 'Partial') ? 'Advance Deposit' : 'Full Payment',
                    'transaction_ref' => 'TXN-INIT-' . $pay->id,
                    'notes' => ($status === 'Partial') ? 'Advance payment recorded at booking' : 'Full payment recorded',
                    'paid_at' => $apt->created_at ?: now(),
                ]);
            }
        }
    }

    /**
     * Auto-sync any existing payment record that has amount_paid > 0 but lacks transaction rows.
     */
    private function syncExistingPaymentTransactions()
    {
        $existingPaymentsWithoutTxns = Payment::where('amount_paid', '>', 0)
            ->whereDoesntHave('transactions')
            ->get();

        foreach ($existingPaymentsWithoutTxns as $ep) {
            $type = ($ep->status === 'Partial') ? 'Advance Deposit' : 'Full Payment';
            $notes = ($ep->status === 'Partial') ? 'Advance payment recorded at booking' : 'Full payment recorded';

            PaymentTransaction::create([
                'payment_id' => $ep->id,
                'amount' => $ep->amount_paid,
                'payment_method' => $ep->payment_method ?: 'Card',
                'transaction_type' => $type,
                'transaction_ref' => $ep->transaction_ref ?: ('TXN-INIT-' . $ep->id),
                'notes' => $notes,
                'paid_at' => $ep->created_at ?: now(),
            ]);
        }
    }
}

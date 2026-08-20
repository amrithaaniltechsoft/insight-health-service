<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\ClinicalNote;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OverviewController extends Controller
{
    public function stats(Request $request)
    {
        $today = Carbon::today();
        
        $todayAppointmentsCount = Appointment::whereDate('appointment_date', $today)->count();
        $totalPatientsCount = Customer::count();
        $pendingNotesCount = ClinicalNote::where('status', 'Draft')->count();
        
        $totalRevenue = Payment::where('status', 'Paid')->sum('amount_paid');

        $recentAppointments = Appointment::with(['patient', 'service', 'staff'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($apt) {
                $pName = $apt->patient ? (($apt->patient->first_name . ' ' . $apt->patient->last_name) ?: ($apt->patient->name ?: 'N/A')) : 'N/A';
                return [
                    'id' => $apt->appointment_code,
                    'patientName' => trim($pName),
                    'service' => $apt->service ? ($apt->service->title ?? $apt->service->service_name) : 'Health Scan',
                    'time' => $apt->start_time,
                    'date' => $apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : '',
                    'status' => $apt->status,
                    'clinician' => $apt->staff ? ($apt->staff->first_name . ' ' . $apt->staff->last_name) : 'Unassigned',
                ];
            });

        return response()->json([
            'status' => 'success',
            'stats' => [
                'todayAppointments' => $todayAppointmentsCount,
                'totalPatients' => $totalPatientsCount,
                'pendingNotes' => $pendingNotesCount,
                'totalRevenue' => (float) $totalRevenue,
            ],
            'recentAppointments' => $recentAppointments,
        ]);
    }
}

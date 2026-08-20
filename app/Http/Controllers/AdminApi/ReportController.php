<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function analytics()
    {
        $monthlyRevenue = Payment::where('status', 'Paid')
            ->selectRaw('MONTH(created_at) as month, SUM(amount_paid) as revenue')
            ->groupBy('month')
            ->get();

        $appointmentStatusCounts = Appointment::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->json([
            'status' => 'success',
            'analytics' => [
                'totalPatients' => Patient::count(),
                'totalAppointments' => Appointment::count(),
                'totalRevenue' => (float) Payment::where('status', 'Paid')->sum('amount_paid'),
                'monthlyRevenue' => $monthlyRevenue,
                'appointmentBreakdown' => $appointmentStatusCounts,
            ]
        ]);
    }
}

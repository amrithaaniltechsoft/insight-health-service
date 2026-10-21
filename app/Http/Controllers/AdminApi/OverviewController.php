<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\ClinicalNote;
use App\Models\AdminNotification;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OverviewController extends Controller
{
    public function stats(Request $request)
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Appointment Counts
        $todayAppointmentsCount = Appointment::whereDate('appointment_date', $today)->count();
        $checkedInTodayCount = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'Checked-In')
            ->count();
        $completedTodayCount = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'Completed')
            ->count();
        $pendingAppointmentsCount = Appointment::whereIn('status', ['Pending', 'Scheduled'])
            ->whereDate('appointment_date', '>=', $today)
            ->count();

        // 2. Patient Counts
        $totalPatientsCount = Customer::count();
        $newPatientsThisWeek = Customer::where('created_at', '>=', $startOfWeek)->count();

        // 3. Clinical Notes
        $pendingNotesCount = ClinicalNote::where('status', 'Draft')->count();

        // 4. Financial Totals
        $totalRevenue = Payment::where('status', 'Paid')->sum('amount_paid');
        $todayRevenue = Payment::where('status', 'Paid')
            ->whereDate('created_at', $today)
            ->sum('amount_paid');
        $monthRevenue = Payment::where('status', 'Paid')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->sum('amount_paid');

        // Helper mapper for appointments
        $mapAppointment = function ($apt) {
            $patientName = 'N/A';
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
                $patientEmail = $p->email ?? ($p->customer ? $p->customer->email : '');
                $patientPhone = $p->phone ?? ($p->customer ? $p->customer->phone : '');
            } else if ($apt->patient_id) {
                $c = Customer::find($apt->patient_id);
                if ($c) {
                    $name = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                    if (empty($name) && !empty($c->name)) $name = $c->name;
                    $patientName = !empty($name) ? $name : ('Customer #' . $c->id);
                    $patientEmail = $c->email ?? '';
                    $patientPhone = $c->phone ?? '';
                }
            }

            $serviceDisplay = 'Health Scan';
            if ($apt->service) {
                $serviceDisplay = $apt->service->title ?? $apt->service->service_name;
            }

            return [
                'id' => $apt->appointment_code,
                'rawId' => $apt->id,
                'patientName' => trim($patientName),
                'patientEmail' => $patientEmail,
                'patientPhone' => $patientPhone,
                'service' => $serviceDisplay,
                'time' => $apt->start_time ? date('g:i A', strtotime($apt->start_time)) : '',
                'rawTime' => $apt->start_time,
                'date' => $apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : '',
                'status' => $apt->status,
                'paymentStatus' => $apt->payment_status,
                'clinician' => $apt->staff ? trim($apt->staff->first_name . ' ' . $apt->staff->last_name) : 'Unassigned',
                'staffId' => $apt->staff_id,
            ];
        };

        // 5. Today's Appointments
        $todayAppointments = Appointment::with(['patient', 'service', 'staff'])
            ->whereDate('appointment_date', $today)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map($mapAppointment);

        // 6. Recent Appointments (Fallback or activity log)
        $recentAppointments = Appointment::with(['patient', 'service', 'staff'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get()
            ->map($mapAppointment);

        // 7. Recent Notifications (Role-scoped to avoid overload)
        $user = $request->user();
        $notifQuery = AdminNotification::orderBy('created_at', 'desc');
        if ($user) {
            $staff = Staff::where('user_id', $user->id)->first();
            $role = $staff ? $staff->role : ($user->role ?? 'super_admin');
            if ($role === 'clinician') {
                $notifQuery->where('user_id', $user->id);
            } else {
                $notifQuery->where('message', 'not like', 'You have been assigned to%')
                    ->where(function ($q) {
                        $q->where('event_type', '!=', 'appointment.assigned')
                          ->orWhereNull('event_type');
                    });
            }
        }


        $notifications = $notifQuery
            ->limit(5)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->type ?? 'System',
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'priority' => $notif->priority ?? 'normal',
                    'isRead' => (bool)$notif->is_read,
                    'time' => $notif->created_at ? $notif->created_at->diffForHumans() : 'Recently',
                ];
            });


        // 8. Staff / Clinicians on duty today
        $clinicians = Staff::where('status', 'active')
            ->get()
            ->map(function ($staffMember) use ($today) {
                $appointmentsCount = Appointment::where('staff_id', $staffMember->id)
                    ->whereDate('appointment_date', $today)
                    ->count();

                return [
                    'id' => $staffMember->id,
                    'name' => trim($staffMember->first_name . ' ' . $staffMember->last_name),
                    'role' => $staffMember->role,
                    'specialization' => $staffMember->specialization ?? 'Clinical Specialist',
                    'availability' => $staffMember->availability ?? 'available',
                    'todayAppointmentsCount' => $appointmentsCount,
                ];
            });

        return response()->json([
            'status' => 'success',
            'stats' => [
                'todayAppointments' => $todayAppointmentsCount,
                'checkedInToday' => $checkedInTodayCount,
                'completedToday' => $completedTodayCount,
                'pendingConfirmations' => $pendingAppointmentsCount,
                'totalPatients' => $totalPatientsCount,
                'newPatientsThisWeek' => $newPatientsThisWeek,
                'pendingNotes' => $pendingNotesCount,
                'totalRevenue' => (float) $totalRevenue,
                'todayRevenue' => (float) $todayRevenue,
                'monthRevenue' => (float) $monthRevenue,
            ],
            'todaySchedule' => $todayAppointments,
            'recentAppointments' => $recentAppointments,
            'notifications' => $notifications,
            'clinicians' => $clinicians,
        ]);
    }
}


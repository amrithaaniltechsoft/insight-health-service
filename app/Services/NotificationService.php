<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Resolve human-readable patient name from Appointment
     */
    public static function resolvePatientName(Appointment $appointment): string
    {
        if ($appointment->relationLoaded('patient') && $appointment->patient) {
            $p = $appointment->patient;
            $name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
            if (!empty($name)) return $name;
            if (!empty($p->name)) return $p->name;
        }

        if ($appointment->patient_id) {
            $p = \App\Models\Patient::find($appointment->patient_id);
            if ($p) {
                $name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                if (!empty($name)) return $name;
                if (!empty($p->name)) return $p->name;
            }

            $c = \App\Models\Customer::find($appointment->patient_id);
            if ($c) {
                $name = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                if (!empty($name)) return $name;
                if (!empty($c->name)) return $c->name;
            }
        }

        return 'Patient';
    }

    /**
     * Resolve service title from Appointment
     */
    public static function resolveServiceTitle(Appointment $appointment): string
    {
        if ($appointment->relationLoaded('service') && $appointment->service) {
            return $appointment->service->title ?? $appointment->service->service_name ?? 'Health Scan';
        }
        if ($appointment->service_id) {
            $s = \App\Models\Service::find($appointment->service_id);
            if ($s) return $s->title ?? $s->service_name ?? 'Health Scan';
        }
        return 'Health Scan';
    }

    /**
     * Find the User account associated with any Staff ID
     */
    public static function getUserForStaff(?int $staffId): ?User
    {
        if (!$staffId) return null;

        $staff = Staff::find($staffId);
        if (!$staff) return null;

        if ($staff->user_id) {
            $user = User::find($staff->user_id);
            if ($user) return $user;
        }

        if (!empty($staff->email)) {
            return User::where('email', $staff->email)->first();
        }

        return null;
    }

    /**
     * Find the User account for a CLINICIAN only.
     * Prevents admin/reception from being targeted for clinician allotment notifications.
     */
    public static function getUserForClinicianStaff(?int $staffId): ?User
    {
        if (!$staffId) return null;

        $staff = Staff::find($staffId);
        if (!$staff || $staff->role !== 'clinician') {
            return null;
        }

        return self::getUserForStaff($staffId);
    }

    /**
     * 1. NEW APPOINTMENT CREATED
     * Audience: Reception & Admins. Clinician ONLY if pre-allotted.
     */
    public static function notifyAppointmentCreated(Appointment $appointment, ?int $actorUserId = null): void
    {
        $patientName = self::resolvePatientName($appointment);
        $serviceTitle = self::resolveServiceTitle($appointment);
        $code = $appointment->appointment_code;
        $dateIso = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('Y-m-d')) : '';
        $dateStr = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('d M Y')) : 'Upcoming';
        $timeStr = $appointment->start_time ? date('g:i A', strtotime($appointment->start_time)) : '';

        // A. Target Reception & Administrators
        $adminStaff = Staff::whereIn('role', ['reception', 'administrator', 'super_admin'])
            ->where('status', 'active')
            ->where(function ($q) use ($appointment) {
                if ($appointment->clinic_id) {
                    $q->where('clinic_id', $appointment->clinic_id)
                      ->orWhereNull('clinic_id')
                      ->orWhere('role', 'super_admin');
                }
            })
            ->get();

        foreach ($adminStaff as $staff) {
            $user = self::getUserForStaff($staff->id);
            if (!$user) continue;

            self::send($user, [
                'clinic_id' => $appointment->clinic_id,
                'actor_id' => $actorUserId,
                'title' => "New Booking: {$code}",
                'message' => "New booking created for {$patientName} ({$serviceTitle}) on {$dateStr} at {$timeStr}.",
                'type' => 'appointment',
                'event_type' => 'appointment.created',
                'priority' => 'normal',
                'link' => "/dashboard/appointments?search={$code}",
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'code' => $code,
                    'patient_name' => $patientName,
                    'service' => $serviceTitle,
                    'date' => $dateIso,
                ],
            ], $actorUserId);
        }

        // B. If a clinician is pre-assigned, alert ONLY that specific clinician and link to their Schedule
        if ($appointment->staff_id) {
            $clinicianUser = self::getUserForClinicianStaff($appointment->staff_id);
            if ($clinicianUser) {
                self::send($clinicianUser, [
                    'clinic_id' => $appointment->clinic_id,
                    'actor_id' => $actorUserId,
                    'title' => "New Assigned Appointment: {$code}",
                    'message' => "You have been assigned to {$patientName} for {$serviceTitle} on {$dateStr} at {$timeStr}.",
                    'type' => 'appointment',
                    'event_type' => 'appointment.assigned',
                    'priority' => 'high',
                    'link' => "/dashboard/schedule?date={$dateIso}&appointment={$code}",
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'code' => $code,
                        'patient_name' => $patientName,
                        'service' => $serviceTitle,
                        'date' => $dateIso,
                    ],
                ], $actorUserId);
            }
        }
    }

    /**
     * 2. APPOINTMENT ALLOTTED TO A CLINICIAN
     * Audience: The newly assigned clinician (high priority).
     * If reassigned, notify the old clinician that their slot is freed.
     */
    public static function notifyAppointmentAssigned(Appointment $appointment, int $newStaffId, ?int $oldStaffId = null, ?int $actorUserId = null): void
    {
        $patientName = self::resolvePatientName($appointment);
        $serviceTitle = self::resolveServiceTitle($appointment);
        $code = $appointment->appointment_code;
        $dateIso = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('Y-m-d')) : '';
        $dateStr = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('d M Y')) : 'Upcoming';
        $timeStr = $appointment->start_time ? date('g:i A', strtotime($appointment->start_time)) : '';

        // Alert the new clinician (links to their Schedule)
        $newUser = self::getUserForClinicianStaff($newStaffId);
        if ($newUser) {
            self::send($newUser, [
                'clinic_id' => $appointment->clinic_id,
                'actor_id' => $actorUserId,
                'title' => "Appointment Assigned: {$code}",
                'message' => "You have been assigned to {$patientName} ({$serviceTitle}) on {$dateStr} at {$timeStr}.",
                'type' => 'appointment',
                'event_type' => 'appointment.assigned',
                'priority' => 'high',
                'link' => "/dashboard/schedule?date={$dateIso}&appointment={$code}",
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'code' => $code,
                    'patient_name' => $patientName,
                    'staff_id' => $newStaffId,
                    'date' => $dateIso,
                ],
            ], $actorUserId);
        }

        // If reassigned from another clinician, inform the previous clinician that slot was released
        if ($oldStaffId && $oldStaffId !== $newStaffId) {
            $oldUser = self::getUserForClinicianStaff($oldStaffId);
            if ($oldUser) {
                self::send($oldUser, [
                    'clinic_id' => $appointment->clinic_id,
                    'actor_id' => $actorUserId,
                    'title' => "Appointment Reassigned: {$code}",
                    'message' => "Appointment {$code} for {$patientName} has been reassigned to another clinician. Your schedule slot is cleared.",
                    'type' => 'appointment',
                    'event_type' => 'appointment.reassigned',
                    'priority' => 'normal',
                    'link' => "/dashboard/schedule?date={$dateIso}",
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'code' => $code,
                        'date' => $dateIso,
                    ],
                ], $actorUserId);
            }
        }
    }

    /**
     * 3. APPOINTMENT RESCHEDULED
     * Audience: Assigned clinician (links to Schedule) and reception (links to Appointments).
     */
    public static function notifyAppointmentRescheduled(Appointment $appointment, string $oldDate, string $oldTime, ?int $actorUserId = null): void
    {
        $patientName = self::resolvePatientName($appointment);
        $code = $appointment->appointment_code;
        $newDateIso = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('Y-m-d')) : '';
        $newDateStr = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('d M Y')) : '';
        $newTimeStr = $appointment->start_time ? date('g:i A', strtotime($appointment->start_time)) : '';
        $formattedOldTime = !empty($oldTime) ? date('g:i A', strtotime($oldTime)) : '';

        // A. Notify assigned clinician (points to Schedule matrix on that date)
        if ($appointment->staff_id) {
            $clinicianUser = self::getUserForClinicianStaff($appointment->staff_id);
            if ($clinicianUser) {
                self::send($clinicianUser, [
                    'clinic_id' => $appointment->clinic_id,
                    'actor_id' => $actorUserId,
                    'title' => "Schedule Changed: {$code}",
                    'message' => "Appointment {$code} for {$patientName} was rescheduled from {$oldDate} ({$formattedOldTime}) to {$newDateStr} at {$newTimeStr}.",
                    'type' => 'appointment',
                    'event_type' => 'appointment.rescheduled',
                    'priority' => 'high',
                    'link' => "/dashboard/schedule?date={$newDateIso}&appointment={$code}",
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'code' => $code,
                        'old_date' => $oldDate,
                        'new_date' => $newDateStr,
                        'date' => $newDateIso,
                    ],
                ], $actorUserId);
            }
        }

        // B. Notify Reception on duty for operational coordination
        $receptionStaff = Staff::where('role', 'reception')
            ->where('status', 'active')
            ->where(function ($q) use ($appointment) {
                if ($appointment->clinic_id) {
                    $q->where('clinic_id', $appointment->clinic_id)->orWhereNull('clinic_id');
                }
            })
            ->get();

        foreach ($receptionStaff as $staff) {
            $user = self::getUserForStaff($staff->id);
            if (!$user) continue;

            self::send($user, [
                'clinic_id' => $appointment->clinic_id,
                'actor_id' => $actorUserId,
                'title' => "Rescheduled: {$code}",
                'message' => "{$patientName} rescheduled to {$newDateStr} at {$newTimeStr}.",
                'type' => 'appointment',
                'event_type' => 'appointment.rescheduled',
                'priority' => 'normal',
                'link' => "/dashboard/appointments?search={$code}",
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'code' => $code,
                    'date' => $newDateIso,
                ],
            ], $actorUserId);
        }
    }

    /**
     * 4. APPOINTMENT CANCELLED
     * Audience: Assigned clinician (links to Schedule) and Reception/Admins.
     */
    public static function notifyAppointmentCancelled(Appointment $appointment, ?string $reason = null, ?int $actorUserId = null): void
    {
        $patientName = self::resolvePatientName($appointment);
        $code = $appointment->appointment_code;
        $dateIso = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('Y-m-d')) : '';
        $dateStr = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('d M Y')) : '';
        $timeStr = $appointment->start_time ? date('g:i A', strtotime($appointment->start_time)) : '';
        $reasonText = !empty($reason) ? " Reason: {$reason}" : '';

        // A. Notify assigned clinician (points to Schedule matrix)
        if ($appointment->staff_id) {
            $clinicianUser = self::getUserForClinicianStaff($appointment->staff_id);
            if ($clinicianUser) {
                self::send($clinicianUser, [
                    'clinic_id' => $appointment->clinic_id,
                    'actor_id' => $actorUserId,
                    'title' => "Appointment Cancelled: {$code}",
                    'message' => "Appointment {$code} for {$patientName} ({$dateStr} at {$timeStr}) was cancelled.{$reasonText} Your slot is now open.",
                    'type' => 'appointment',
                    'event_type' => 'appointment.cancelled',
                    'priority' => 'urgent',
                    'link' => "/dashboard/schedule?date={$dateIso}",
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'code' => $code,
                        'reason' => $reason,
                        'date' => $dateIso,
                    ],
                ], $actorUserId);
            }
        }

        // B. Notify Reception so they can backfill or adjust the clinic queue
        $receptionStaff = Staff::whereIn('role', ['reception', 'administrator'])
            ->where('status', 'active')
            ->where(function ($q) use ($appointment) {
                if ($appointment->clinic_id) {
                    $q->where('clinic_id', $appointment->clinic_id)->orWhereNull('clinic_id');
                }
            })
            ->get();

        foreach ($receptionStaff as $staff) {
            $user = self::getUserForStaff($staff->id);
            if (!$user) continue;

            self::send($user, [
                'clinic_id' => $appointment->clinic_id,
                'actor_id' => $actorUserId,
                'title' => "Cancellation: {$code}",
                'message' => "{$patientName} cancelled {$code} on {$dateStr} at {$timeStr}.{$reasonText}",
                'type' => 'appointment',
                'event_type' => 'appointment.cancelled',
                'priority' => 'high',
                'link' => "/dashboard/appointments?search={$code}",
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'code' => $code,
                    'date' => $dateIso,
                ],
            ], $actorUserId);
        }
    }

    /**
     * 5. PATIENT CHECKED IN / ARRIVED AT CLINIC
     * Audience: Assigned clinician (Urgent priority - points to Schedule matrix).
     */
    public static function notifyAppointmentCheckedIn(Appointment $appointment, ?int $actorUserId = null): void
    {
        $patientName = self::resolvePatientName($appointment);
        $code = $appointment->appointment_code;
        $dateIso = $appointment->appointment_date ? (is_string($appointment->appointment_date) ? $appointment->appointment_date : $appointment->appointment_date->format('Y-m-d')) : '';
        $timeStr = $appointment->start_time ? date('g:i A', strtotime($appointment->start_time)) : '';

        if ($appointment->staff_id) {
            $clinicianUser = self::getUserForClinicianStaff($appointment->staff_id);
            if ($clinicianUser) {
                self::send($clinicianUser, [
                    'clinic_id' => $appointment->clinic_id,
                    'actor_id' => $actorUserId,
                    'title' => "Patient Arrived: {$patientName}",
                    'message' => "{$patientName} ({$code}) has checked in for the {$timeStr} consultation and is waiting in the reception lounge.",
                    'type' => 'clinical',
                    'event_type' => 'appointment.checked_in',
                    'priority' => 'urgent',
                    'link' => "/dashboard/schedule?date={$dateIso}&appointment={$code}",
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'code' => $code,
                        'patient_name' => $patientName,
                        'date' => $dateIso,
                    ],
                ], $actorUserId);
            }
        }
    }


    /**
     * Core Dispatcher with Anti-Overload & Actor Suppression Rules
     */
    protected static function send(User $recipient, array $payload, ?int $actorUserId = null): ?AdminNotification
    {
        // RULE 1: Actor suppression (Never notify the person who triggered the action)
        if ($actorUserId && $recipient->id === $actorUserId) {
            return null;
        }

        // RULE 2: Anti-spam / Debounce throttling
        // Check if an identical notification was sent to this recipient in the last 60 seconds
        $oneMinuteAgo = Carbon::now()->subSeconds(60);
        $recentDuplicate = AdminNotification::where('user_id', $recipient->id)
            ->where('title', $payload['title'])
            ->where('created_at', '>=', $oneMinuteAgo)
            ->first();

        if ($recentDuplicate) {
            // Already delivered within 60s, avoid spamming recipient
            return $recentDuplicate;
        }

        // Prepare record attributes safely checking table schema
        $insertData = [
            'user_id' => $recipient->id,
            'title' => $payload['title'],
            'message' => $payload['message'],
            'type' => $payload['type'] ?? 'appointment',
            'is_read' => false,
            'link' => $payload['link'] ?? null,
        ];

        // Gracefully populate enhanced columns if present in schema
        if (Schema::hasColumn('admin_notifications', 'clinic_id') && isset($payload['clinic_id'])) {
            $insertData['clinic_id'] = $payload['clinic_id'];
        }
        if (Schema::hasColumn('admin_notifications', 'actor_id') && $actorUserId) {
            $insertData['actor_id'] = $actorUserId;
        }
        if (Schema::hasColumn('admin_notifications', 'event_type') && isset($payload['event_type'])) {
            $insertData['event_type'] = $payload['event_type'];
        }
        if (Schema::hasColumn('admin_notifications', 'priority') && isset($payload['priority'])) {
            $insertData['priority'] = $payload['priority'];
        }
        if (Schema::hasColumn('admin_notifications', 'metadata') && isset($payload['metadata'])) {
            $insertData['metadata'] = $payload['metadata'];
        }

        return AdminNotification::create($insertData);
    }
}

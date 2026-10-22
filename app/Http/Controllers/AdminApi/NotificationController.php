<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Staff;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $staff = Staff::where('user_id', $user->id)->orWhere('email', $user->email)->first();
        $userRole = $staff ? $staff->role : ($user->role ?? 'super_admin');

        $query = AdminNotification::query();

        // Role-based targeting rule:
        // - Clinicians: strictly see notifications assigned to their own user account, never receptionist alerts
        // - Reception: see reception alerts and reassignment notices, but NEVER clinician personal allotment alerts ("You have been assigned to...") or slot-clearing alerts ("Your schedule slot is cleared.")
        // - Admins: see operational/clinic alerts, but NEVER clinician allotments, slot-clearing alerts, or receptionist reassignment alerts
        if ($userRole === 'clinician') {
            $query->where('user_id', $user->id)
                  ->where(function ($q) {
                      $q->where('event_type', '!=', 'appointment.reassigned_reception')
                        ->orWhereNull('event_type');
                  });
        } else if ($userRole === 'reception') {
            $query->where(function ($q) use ($user, $staff) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($sub) use ($staff) {
                      $sub->whereNull('user_id');
                      if ($staff && $staff->clinic_id) {
                          $sub->where(function ($cq) use ($staff) {
                              $cq->where('clinic_id', $staff->clinic_id)->orWhereNull('clinic_id');
                          });
                      }
                  });
            })
            ->where('message', 'not like', 'You have been assigned to%')
            ->where('message', 'not like', '%Your schedule slot is cleared%')
            ->where(function ($q) {
                $q->whereNotIn('event_type', ['appointment.assigned', 'appointment.reassigned_clinician', 'appointment.reassigned'])
                  ->orWhereNull('event_type');
            });
        } else {
            // Admin & Super Admin: see system/clinic alerts, but never personal clinician allotment alerts or reassignment notices
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->where('message', 'not like', 'You have been assigned to%')
            ->where('message', 'not like', '%Your schedule slot is cleared%')
            ->where(function ($q) {
                $q->whereNotIn('event_type', [
                    'appointment.assigned',
                    'appointment.reassigned_clinician',
                    'appointment.reassigned_reception',
                    'appointment.reassigned'
                ])
                ->orWhereNull('event_type');
            });
        }


        // Filter: Type
        if ($request->has('type') && !empty($request->type) && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter: Priority
        if ($request->has('priority') && !empty($request->priority) && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Filter: Unread Only
        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $unreadCount = (clone $query)->where('is_read', false)->count();

        $notifications = $query->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'eventType' => $notif->event_type ?? null,
                    'priority' => $notif->priority ?? 'normal',
                    'isRead' => (bool)$notif->is_read,
                    'link' => $notif->link,
                    'metadata' => $notif->metadata ?? null,
                    'time' => $notif->created_at ? $notif->created_at->diffForHumans() : 'Recently',
                    'createdAt' => $notif->created_at ? $notif->created_at->toIso8601String() : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'unreadCount' => $unreadCount,
            'data' => $notifications
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = AdminNotification::findOrFail($id);

        // Security check: only recipient or admin can mark as read
        if ($notification->user_id && $notification->user_id !== $user->id) {
            $staff = Staff::where('user_id', $user->id)->first();
            if (!$staff || !in_array($staff->role, ['super_admin', 'administrator'])) {
                return response()->json(['message' => 'Unauthorized to update this notification'], 403);
            }
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        AdminNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
}


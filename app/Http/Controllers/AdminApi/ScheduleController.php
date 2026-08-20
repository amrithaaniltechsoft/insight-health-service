<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\StaffSchedule;
use App\Models\Staff;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffSchedule::with(['staff', 'clinic']);

        if ($request->has('staff_id') && $request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        $schedules = $query->orderBy('shift_date', 'asc')->get()->map(function($sched) {
            return [
                'id' => $sched->id,
                'staffName' => $sched->staff ? ($sched->staff->first_name . ' ' . $sched->staff->last_name) : 'Staff Member',
                'clinic' => $sched->clinic ? $sched->clinic->name : 'Main Clinic',
                'date' => $sched->shift_date ? $sched->shift_date->format('Y-m-d') : '',
                'startTime' => $sched->start_time,
                'endTime' => $sched->end_time,
                'status' => $sched->status,
                'notes' => $sched->notes,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required',
            'shift_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'clinic_id' => 'nullable',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $schedule = StaffSchedule::create([
            'staff_id' => $validated['staff_id'],
            'clinic_id' => $validated['clinic_id'] ?? null,
            'shift_date' => $validated['shift_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'] ?? 'Scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shift schedule created successfully',
            'data' => $schedule
        ], 201);
    }
}

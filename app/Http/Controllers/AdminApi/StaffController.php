<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $staffList = Staff::with('clinic')->get()->map(function($s) {
            return [
                'id' => $s->staff_code,
                'rawId' => $s->id,
                'name' => $s->first_name . ' ' . $s->last_name,
                'firstName' => $s->first_name,
                'lastName' => $s->last_name,
                'email' => $s->email,
                'phone' => $s->phone,
                'role' => $s->role,
                'clinic' => $s->clinic ? $s->clinic->name : 'All Clinics',
                'clinicId' => $s->clinic_id,
                'specialization' => $s->specialization,
                'status' => $s->status,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $staffList
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:staff,email',
            'role' => 'required|in:super_admin,administrator,reception,clinician',
            'phone' => 'nullable|string',
            'clinic_id' => 'nullable',
            'specialization' => 'nullable|string',
            'password' => 'nullable|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'insight123'),
            'role' => $validated['role'],
        ]);

        $staffCode = 'STF-' . rand(1000, 9999);

        $staff = Staff::create([
            'user_id' => $user->id,
            'staff_code' => $staffCode,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'clinic_id' => $validated['clinic_id'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member created successfully',
            'data' => $staff
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::where('id', $id)->orWhere('staff_code', $id)->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'role' => 'nullable|in:super_admin,administrator,reception,clinician',
            'phone' => 'nullable|string',
            'clinic_id' => 'nullable',
            'specialization' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $data = array_filter($validated, function($v) {
            return !is_null($v);
        });
        if (!empty($data)) {
            $staff->update($data);
        }

        if ($staff->user_id && isset($validated['role'])) {
            User::where('id', $staff->user_id)->update(['role' => $validated['role']]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member updated successfully',
            'data' => $staff
        ]);
    }
}

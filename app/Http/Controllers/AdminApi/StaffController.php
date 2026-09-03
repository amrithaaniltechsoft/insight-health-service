<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::with('clinic');

        if ($request->has('role') && !empty($request->role)) {
            $query->where('role', $request->role);
        }

        $staffList = $query->get()->map(function($s) {
            return [
                'id' => $s->staff_code ?: ('STF-' . str_pad($s->id, 4, '0', STR_PAD_LEFT)),
                'rawId' => $s->id,
                'name' => trim($s->first_name . ' ' . $s->last_name),
                'firstName' => $s->first_name,
                'lastName' => $s->last_name,
                'email' => $s->email,
                'phone' => $s->phone ?? 'N/A',
                'role' => $s->role,
                'clinic' => $s->clinic ? $s->clinic->name : 'Main Healthcare Clinic',
                'clinicId' => $s->clinic_id,
                'specialization' => $s->specialization ?? 'Healthcare Specialist',
                'status' => $s->status ?? 'active',
                'availability' => $s->availability ?? 'available',
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
            'availability' => 'nullable|in:available,unavailable',
        ]);

        // Find existing user or create new User record
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? 'Password123!'),
            ]);
        }

        $staffCode = 'STF-' . rand(1000, 9999);

        $staff = Staff::create([
            'user_id' => $user->id,
            'staff_code' => $staffCode,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'clinic_id' => $validated['clinic_id'] ?? 1,
            'specialization' => $validated['specialization'] ?? 'Healthcare Specialist',
            'status' => 'active',
            'availability' => $validated['availability'] ?? 'available',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member created successfully',
            'data' => [
                'id' => $staff->staff_code,
                'rawId' => $staff->id,
                'name' => trim($staff->first_name . ' ' . $staff->last_name),
                'firstName' => $staff->first_name,
                'lastName' => $staff->last_name,
                'email' => $staff->email,
                'phone' => $staff->phone ?? 'N/A',
                'role' => $staff->role,
                'specialization' => $staff->specialization,
                'status' => $staff->status,
                'availability' => $staff->availability,
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $staff = Staff::where('id', $id)
            ->orWhere('id', $rawId)
            ->orWhere('staff_code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'role' => 'nullable|in:super_admin,administrator,reception,clinician',
            'phone' => 'nullable|string',
            'clinic_id' => 'nullable',
            'specialization' => 'nullable|string',
            'status' => 'nullable|string',
            'availability' => 'nullable|in:available,unavailable',
        ]);

        $data = array_filter($validated, function($v) {
            return !is_null($v);
        });

        if (!empty($data)) {
            $staff->update($data);
        }

        if ($staff->user_id) {
            $user = User::find($staff->user_id);
            if ($user) {
                $userData = [];
                if (!empty($validated['first_name']) || !empty($validated['last_name'])) {
                    $fName = $validated['first_name'] ?? $staff->first_name;
                    $lName = $validated['last_name'] ?? $staff->last_name;
                    $userData['name'] = trim($fName . ' ' . $lName);
                }
                if (!empty($validated['email'])) {
                    $userData['email'] = $validated['email'];
                }
                if (!empty($userData)) {
                    $user->update($userData);
                }

                // Revoke active sessions ONLY if account access status is set to inactive / revoked
                if (isset($validated['status']) && ($validated['status'] === 'inactive' || $validated['status'] === 'revoked')) {
                    $user->tokens()->delete();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member updated successfully',
            'data' => $staff
        ]);
    }
}

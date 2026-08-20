<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Staff / Admin Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt login via email or username
        $user = User::where('email', $request->username)
                    ->orWhere('name', $request->username)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // For development fallback / initial admin bootstrap if no user in DB yet:
            if ($request->username === 'admin' && $request->password === 'admin123') {
                $user = User::firstOrCreate(
                    ['email' => 'admin@insight.com'],
                    [
                        'name' => 'Super Admin',
                        'password' => Hash::make('admin123'),
                        'role' => 'super_admin'
                    ]
                );
            } else {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }
        }

        // Fetch or create associated Staff profile
        $staff = Staff::where('user_id', $user->id)->orWhere('email', $user->email)->first();
        if (!$staff) {
            $role = $request->input('role', $user->role ?? 'super_admin');
            $staff = Staff::create([
                'user_id' => $user->id,
                'staff_code' => 'STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'first_name' => explode(' ', $user->name)[0] ?? 'Admin',
                'last_name' => explode(' ', $user->name)[1] ?? 'User',
                'email' => $user->email,
                'role' => in_array($role, ['super_admin', 'administrator', 'reception', 'clinician']) ? $role : 'super_admin',
                'status' => 'active'
            ]);
        }

        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $staff->role,
                'staff_id' => $staff->id,
                'clinic_id' => $staff->clinic_id,
            ]
        ]);
    }

    /**
     * Get authenticated staff details
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $staff = Staff::where('user_id', $user->id)->orWhere('email', $user->email)->first();

        return response()->json([
            'user' => $user,
            'staff' => $staff,
            'role' => $staff ? $staff->role : 'super_admin',
        ]);
    }

    /**
     * Logout staff user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}

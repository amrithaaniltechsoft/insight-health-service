<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function index()
    {
        $clinics = Clinic::all();
        if ($clinics->isEmpty()) {
            // Seed default main clinic if none exists
            $clinics = collect([
                Clinic::create([
                    'name' => 'Insight Main Clinic',
                    'code' => 'CLN-LON',
                    'address' => '123 Healthcare Way, London',
                    'phone' => '+44 20 7946 0912',
                    'email' => 'info@insighthealth.co.uk',
                    'total_rooms' => 5,
                    'status' => 'active'
                ])
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $clinics
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:clinics,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'total_rooms' => 'nullable|integer',
        ]);

        $clinic = Clinic::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Clinic created successfully',
            'data' => $clinic
        ], 201);
    }
}

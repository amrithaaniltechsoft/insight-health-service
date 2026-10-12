<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get the current global clinic settings.
     */
    public function show()
    {
        $settings = ClinicSetting::first();

        if (!$settings) {
            $settings = ClinicSetting::create([
                'opening_time' => '09:00',
                'closing_time' => '17:00',
                'public_holidays' => '25 Dec, 01 Jan',
                'same_day_booking' => 'Allowed (Up to 2 hrs before)',
                'cancellation_deadline_hours' => 24,
                'deposit_amounts' => "£50.00 standard deposit for booking. Special scans require 50% deposit.",
                'payment_rules' => "- All card transactions are subject to a 1.5% fee.\n- Corporate bookings must be paid in full 7 days prior.\n- Cash payments are only accepted at the main reception.",
                'refund_policies' => "- No refund for cancellations made within 24 hours.\n- Refund processing takes 5-7 business days.\n- Partial refunds are issued as clinic credits.",
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $settings,
        ]);
    }

    /**
     * Update or save the global clinic settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'opening_time' => 'nullable|string',
            'closing_time' => 'nullable|string',
            'public_holidays' => 'nullable|string',
            'same_day_booking' => 'nullable|string',
            'cancellation_deadline_hours' => 'nullable|integer|min:0',
            'deposit_amounts' => 'nullable|string',
            'payment_rules' => 'nullable|string',
            'refund_policies' => 'nullable|string',
        ]);

        $settings = ClinicSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = ClinicSetting::create($validated);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Clinic settings updated successfully.',
            'data' => $settings,
        ]);
    }
}

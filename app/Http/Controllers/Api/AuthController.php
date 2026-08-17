<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = $request->email;

        DB::table('otps')->where('email', $email)->where('used', false)->where('expires_at', '<', now())->update(['used' => true]);

        $existingValid = DB::table('otps')->where('email', $email)->where('used', false)->where('expires_at', '>', now())->first();
        if ($existingValid) {
            return response()->json(['message' => 'A valid OTP has already been sent to this email.'], 429);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('otps')->insert([
            'email'      => $email,
            'otp'        => $otp,
            'used'       => false,
            'expires_at' => Carbon::now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($email)->send(new SendOtpMail($otp));

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp'   => 'required|string|size:6',
        ]);

        $record = DB::table('otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        DB::table('otps')->where('id', $record->id)->update(['used' => true]);

        return response()->json(['message' => 'OTP verified successfully.']);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'email'      => 'required|email|max:255|unique:customers,email',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'gender'     => 'required|string|in:Male,Female,Other',
            'dob'        => 'required|date',
        ]);

        $customer = Customer::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'gender'     => $request->gender,
            'dob'        => $request->dob,
            'password'   => Hash::make(Str::random(32)),
        ]);

        return response()->json([
            'message' => 'Account created successfully.',
            'customer' => [
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'email'      => $customer->email,
            ],
        ]);
    }
}

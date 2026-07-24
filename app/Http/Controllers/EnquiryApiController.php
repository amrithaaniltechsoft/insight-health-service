<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Mail\EnquiryMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EnquiryApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'message'    => 'required|string',
        ]);

        $enquiry = Enquiry::create($validated);

        $toEmail = env('ENQUIRY_TO_EMAIL', 'bookings@insighthealthservices.co.uk');
        try {
            Mail::to($toEmail)->send(new EnquiryMail($validated));
        } catch (\Exception $e) {
            // Email is best-effort; enquiry is still saved
        }

        return response()->json(['success' => true, 'message' => 'Enquiry submitted successfully.'], 201);
    }
}

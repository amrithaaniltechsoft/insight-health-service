<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
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

        $phone = $validated['phone'] ?? 'N/A';
        $toEmail = env('ENQUIRY_TO_EMAIL', 'techsofttest@gmail.com');
        try {
            Mail::raw(
                "New enquiry from {$validated['first_name']} {$validated['last_name']}\n" .
                "Email: {$validated['email']}\n" .
                "Phone: {$phone}\n\n" .
                "Message:\n{$validated['message']}",
                function ($message) use ($toEmail, $validated) {
                    $message->to($toEmail)
                            ->subject("Enquiry from {$validated['first_name']} {$validated['last_name']}")
                            ->replyTo($validated['email']);
                }
            );
        } catch (\Exception $e) {
            // Email is best-effort; still return success
        }

        return response()->json(['success' => true, 'message' => 'Enquiry submitted successfully.'], 201);
    }
}

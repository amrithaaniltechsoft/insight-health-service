<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function adminIndex()
    {
        $contact = Contact::first();
        return view('contacts.admin.index', compact('contact'));
    }

    public function update(Request $request)
    {
        $contact = Contact::first();
        if (!$contact) {
            $contact = Contact::create($request->only(['contact1', 'contact2', 'email', 'address']));
        }

        $validated = $request->validate([
            'contact1' => 'nullable|string|max:255',
            'contact2' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'mon_fri' => 'nullable|string|max:255',
            'saturday' => 'nullable|string|max:255',
            'sunday' => 'nullable|string|max:255',
        ]);

        $contact->update($validated);
        return redirect()->route('contacts.admin.index')->with('success', 'Contact updated successfully!');
    }

    public function getPublicContact()
    {
        $contact = Contact::first();
        if (!$contact) {
            return response()->json([
                'contact1' => '01922 351933',
                'contact2' => '07777 138 166',
                'email' => 'bookings@insighthealthservices.co.uk',
                'address' => '1a Walsall Rd, Walsall WS5 4QL, United Kingdom',
                'mon_fri' => '08:00 - 21:00',
                'saturday' => '08:00 - 21:00',
                'sunday' => '08:00 - 21:00',
            ]);
        }
        return response()->json([
            'contact1' => $contact->contact1,
            'contact2' => $contact->contact2,
            'email' => $contact->email,
            'address' => $contact->address,
            'mon_fri' => $contact->mon_fri,
            'saturday' => $contact->saturday,
            'sunday' => $contact->sunday,
        ]);
    }
}

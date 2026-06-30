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
}

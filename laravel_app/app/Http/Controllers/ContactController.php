<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        // Send email to sultan@niufin.cloud
        Mail::to('sultan@niufin.cloud')->send(new ContactFormMail($validatedData));

        return back()->with('success', 'Thank you for contacting us! We have received your message and will get back to you soon.');
    }
}

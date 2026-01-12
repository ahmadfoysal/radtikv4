<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot field check - if filled, it's a bot
        if ($request->filled('website')) {
            // Silently fail for bots
            return redirect()->back()
                ->with('success', 'Thank you for contacting us! We\'ll get back to you soon.');
        }

        // Rate limiting check - max 2 messages per hour
        $contactCount = $request->cookie('contact_messages', 0);
        $lastReset = $request->cookie('contact_reset_time', now()->timestamp);

        // Reset counter if an hour has passed
        if (now()->timestamp - $lastReset >= 3600) {
            $contactCount = 0;
            $lastReset = now()->timestamp;
        }

        if ($contactCount >= 2) {
            return redirect()->back()
                ->withErrors(['message' => 'You have reached the maximum number of messages (2) per hour. Please try again later.'])
                ->withInput();
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'subject' => $request->subject,
            'message' => $request->message,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Increment contact count and set cookies
        $newCount = $contactCount + 1;

        return redirect()->back()
            ->with('success', 'Thank you for contacting us! We\'ll get back to you soon.')
            ->withCookie(cookie('contact_messages', $newCount, 60)) // 60 minutes
            ->withCookie(cookie('contact_reset_time', $lastReset, 60)); // 60 minutes
    }
}

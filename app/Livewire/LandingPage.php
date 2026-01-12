<?php

namespace App\Livewire;

use App\Models\ContactMessage;
use App\Models\Package;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cookie;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class LandingPage extends Component
{
    use Toast;

    // Contact form fields
    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['required', 'email', 'max:255'])]
    public string $email = '';

    #[Rule(['nullable', 'string', 'max:20'])]
    public string $whatsapp = '';

    #[Rule(['required', 'string', 'max:255'])]
    public string $subject = '';

    #[Rule(['required', 'string', 'max:5000'])]
    public string $message = '';

    // Honeypot field
    public string $website = '';

    public bool $showSuccess = false;

    public function mount()
    {
        $this->showSuccess = false;
    }

    public function submitContact()
    {
        // Honeypot check - if filled, it's a bot
        if (!empty($this->website)) {
            // Silently fail for bots - show success without saving
            $this->showSuccess = true;
            $this->reset(['name', 'email', 'whatsapp', 'subject', 'message', 'website']);
            return;
        }

        // Rate limiting check - max 2 messages per hour
        $contactCount = request()->cookie('contact_messages', 0);
        $lastReset = request()->cookie('contact_reset_time', now()->timestamp);

        // Reset counter if an hour has passed
        if (now()->timestamp - $lastReset >= 3600) {
            $contactCount = 0;
            $lastReset = now()->timestamp;
        }

        if ($contactCount >= 2) {
            $this->addError('message', 'You have reached the maximum number of messages (2) per hour. Please try again later.');
            return;
        }

        // Validate form
        $this->validate();

        // Save contact message
        ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'subject' => $this->subject,
            'message' => $this->message,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Set cookies for rate limiting
        $newCount = $contactCount + 1;
        Cookie::queue('contact_messages', $newCount, 60); // 60 minutes
        Cookie::queue('contact_reset_time', $lastReset, 60); // 60 minutes

        // Reset form first
        $this->reset(['name', 'email', 'whatsapp', 'subject', 'message', 'website']);

        // Then show success message
        $this->showSuccess = true;
        $this->success('Thank you for contacting us! We\'ll get back to you soon.');
    }

    public function render()
    {
        $packages = Package::where('is_active', true)
            ->orderBy('price_monthly', 'asc')
            ->get();

        return view('livewire.landing-page', compact('packages'))
            ->layout('layouts.landing');
    }
}

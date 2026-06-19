<?php

namespace App\Mail;

use App\Models\ContactUs;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestContactUsAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ContactUs $contact;

    public function __construct(ContactUs $contact)
    {
        $this->contact = $contact;
    }

    public function build(): self
    {
        return $this->subject('Neue Kontaktanfrage von einem Gast')
            ->view('emails.guest_contact_us_admin_notification')
            ->with([
                'contact' => $this->contact,
            ]);
    }
}
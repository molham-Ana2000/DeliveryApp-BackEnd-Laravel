<?php

namespace App\Services\Api\Guest;

use App\Mail\GuestContactUsAdminNotification;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GuestContactUsService
{
    public function createGuest(array $data): ContactUs
    {
        $contact = ContactUs::create([
            'user_id' => null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'new',
        ]);

        $this->notifyAdminsForNewGuestContactMessage($contact);

        return $contact;
    }

    private function notifyAdminsForNewGuestContactMessage(ContactUs $contact): void
    {
        $admins = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(
                    new GuestContactUsAdminNotification($contact)
                );
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
<?php

namespace App\Services\Api;

use App\Mail\ContactUsAdminNotification;
use App\Mail\ContactUsReplyMail;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class ContactUsService
{
 

public function createCustomer(User $user, array $data): ContactUs
{
    $fullName = trim($user->first_name . ' ' . $user->last_name);

    $contact = ContactUs::create([
        'user_id' => $user->id,
        'name' => $fullName !== '' ? $fullName : $user->email,
        'email' => $user->email,
        'phone' => $data['phone'] ?? $user->phone,
        'subject' => $data['subject'],
        'message' => $data['message'],
        'status' => 'new',
    ]);

    $this->notifyAdminsForNewContactMessage($contact);

    return $contact;
}
private function notifyAdminsForNewContactMessage(ContactUs $contact): void
{
    $admins = User::query()
        ->where('role', 'admin')
        ->whereNotNull('email')
        ->get();

    foreach ($admins as $admin) {
        Mail::to($admin->email)->send(new ContactUsAdminNotification($contact));
    }
}

    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return ContactUs::query()
            ->with([
                'user:id,first_name,last_name,email,phone',
                'repliedBy:id,first_name,last_name,email',
            ])

            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })

            ->when(($filters['type'] ?? null) === 'guest', function ($query) {
                $query->whereNull('user_id');
            })

            ->when(($filters['type'] ?? null) === 'customer', function ($query) {
                $query->whereNotNull('user_id');
            })

            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })

            ->when(!empty($filters['start_date']), function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            })

            ->when(!empty($filters['end_date']), function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            })

            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function markAsRead(ContactUs $contactUs): ContactUs
    {
        if ($contactUs->status === 'new') {
            $contactUs->update([
                'status' => 'read',
            ]);
        }

        return $contactUs->refresh();
    }

    public function close(ContactUs $contactUs): ContactUs
    {
        $contactUs->update([
            'status' => 'closed',
        ]);

        return $contactUs->refresh();
    }

    public function reply(ContactUs $contactUs, User $admin, string $replyMessage): ContactUs
    {
        Mail::to($contactUs->email)->send(
            new ContactUsReplyMail($contactUs, $replyMessage)
        );

        $contactUs->update([
            'admin_reply' => $replyMessage,
            'replied_by' => $admin->id,
            'replied_at' => now(),
            'status' => 'replied',
        ]);

        return $contactUs->refresh();
    }
    public function paginateForCustomer(User $user, array $filters): LengthAwarePaginator
    {
        return ContactUs::query()
            ->select([
                'id',
                'user_id',
                'replied_by',
                'subject',
                'admin_reply',
                'status',
                'created_at',
                'replied_at',
            ])
            ->with([
                'repliedBy:id,first_name,last_name',
            ])
            ->where('user_id', $user->id)
            ->whereNotNull('admin_reply')
            ->when(!empty($filters['start_date']), function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->through(function ($message) {
                return [
                    'id' => $message->id,
                    'subject' => $message->subject,
                    'admin_message' => $message->admin_reply,
                    'admin_name' => $message->repliedBy
                        ? trim($message->repliedBy->first_name . ' ' . $message->repliedBy->last_name)
                        : null,
                    'status' => $message->status,
                    'sent_at' => $message->created_at,
                    'replied_at' => $message->replied_at,
                ];
            });
    }
}
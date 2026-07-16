<?php

namespace App\Services;

use App\Models\UserDeviceToken;
use Throwable;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($this->stringifyData($data));

        app('firebase.messaging')->send($message);
    }

    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = UserDeviceToken::where('user_id', $userId)->pluck('token');

        foreach ($tokens as $token) {
            try {
                $this->sendToToken($token, $title, $body, $data);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    public function sendToAdmins(string $title, string $body, array $data = []): void
    {
        $tokens = UserDeviceToken::query()
            ->whereHas('user', function ($query) {
                $query->where('role', 'admin');
            })
            ->pluck('token');

        foreach ($tokens as $token) {
            try {
                $this->sendToToken($token, $title, $body, $data);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    private function stringifyData(array $data): array
    {
        return collect($data)
            ->map(fn ($value) => (string) $value)
            ->all();
    }
}

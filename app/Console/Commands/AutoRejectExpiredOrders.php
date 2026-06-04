<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Notification;
use App\Models\OrderStatusHistory;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoRejectExpiredOrders extends Command
{
    protected $signature = 'orders:auto-reject-expired';

    protected $description = 'Auto reject expired pending orders';

    public function handle(): int
    {
        $now = now();

        $orders = Order::query()
            ->where('status', 'pending')
            ->where('expires_at', '<=', $now)
            ->with('customer')
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $now) {
                $oldStatus = $order->status;

                $order->update([
                    'status' => 'auto_rejected',
                    'auto_rejected_at' => $now,
                    'rejected_at' => $now,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'changed_by' => null,
                    'old_status' => $oldStatus,
                    'new_status' => 'auto_rejected',
                    'note' => 'Order automatically rejected because it expired.',
                    'created_at' => $now,
                ]);

                Notification::create([
                    'user_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'title' => 'Order Auto Rejected',
                    'body' => "Your order #{$order->order_number} was automatically rejected because it expired.",
                    'is_read' => false,
                ]);
            });

            try {
                app(FirebaseNotificationService::class)->sendToUser(
                    $order->customer_id,
                    'Order Auto Rejected',
                    "Your order #{$order->order_number} was automatically rejected because it expired.",
                    [
                        'type' => 'order_status',
                        'order_id' => (string) $order->id,
                        'status' => 'auto_rejected',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Auto rejected {$orders->count()} orders.");

        return self::SUCCESS;
    }
}
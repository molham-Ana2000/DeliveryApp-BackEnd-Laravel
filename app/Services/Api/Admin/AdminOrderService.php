<?php
namespace App\Services\Api\Admin;

use App\Mail\OrderStatusNotification;
use App\Models\EmailLog;
use App\Models\Loss;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Mail;
class AdminOrderService
{
    private function createCustomerNotification(Order $order, string $title, string $body): void
    {
        Notification::create([
            'user_id' => $order->customer_id,
            'order_id' => $order->id,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
        ]);
    }
    public function approveOrder(int $adminId, int $orderId, array $data): Order
    {
        return DB::transaction(function () use ($adminId, $orderId, $data) {
            $order = Order::query()
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'pending') {
                throw new Exception('Only pending orders can be approved.');
            }

            $oldStatus = $order->status;

            $deliveryCost = (float) $data['delivery_cost'];
            $orderTotal = (float) $order->items_total + $deliveryCost;

            $order->update([
                'status' => 'approved',
                'delivery_cost' => $deliveryCost,
                'order_total' => $orderTotal,
                'approved_at' => now(),
                'approved_by' => $adminId,
                'estimated_delivery_time' => $data['estimated_delivery_time'] ?? null,
            ]);
            

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'changed_by' => $adminId,
                'old_status' => $oldStatus,
                'new_status' => 'approved',
                'note' => $data['note'] ?? 'Order approved by admin.',
                'created_at' => now(),
            ]);
            // Mail::to($order->customer->email)->send(
            // new OrderStatusNotification($order, 'approved', $data['note'] ?? null));

            // Log the email
            EmailLog::create([
                'order_id' => $order->id,
                'user_id' => $adminId,
                'email' => $order->customer->email,
                'subject' => "Your order #{$order->order_number} has been approved",
                'body' => view('emails.order_status_notification', [
                    'order' => $order,
                    'status' => 'approved',
                    'note' => $data['note'] ?? null,
                ])->render(),
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);
            $this->createCustomerNotification(
                $order,
                'Order Approved',
                "Your order #{$order->order_number} has been approved."
            );
           try {
                app(FirebaseNotificationService::class)->sendToUser(
                    $order->customer_id,
                    'Order Approved',
                    "Your order #{$order->order_number} has been approved.",
                    [
                        'type' => 'order_status',
                        'order_id' => $order->id,
                        'status' => 'approved',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
            return $order->load(['items', 'customer']);
        });
    }
    public function rejectOrder(int $adminId, int $orderId, array $data): Order
    {
        return DB::transaction(function () use ($adminId, $orderId, $data) {
            $order = Order::query()->where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'pending') {
                throw new Exception('Only pending orders can be rejected.');
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $adminId,
                'admin_rejection_reason' => $data['admin_rejection_reason'],
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'changed_by' => $adminId,
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'note' => $data['admin_rejection_reason'],
                'created_at' => now(),
            ]);

            // Send email notification
            // Mail::to($order->customer->email)->send(
            //     new OrderStatusNotification($order, 'rejected', $data['admin_rejection_reason'])
            // );

            // Log the email
            EmailLog::create([
                'order_id' => $order->id,
                'user_id' => $adminId,
                'email' => $order->customer->email,
                'subject' => "Your order #{$order->order_number} has been rejected",
                'body' => view('emails.order_status_notification', [
                    'order' => $order,
                    'status' => 'rejected',
                    'note' => $data['admin_rejection_reason'],
                ])->render(),
                'sent_at' => now(),
            ]);
              $this->createCustomerNotification(
                $order,
                'Order Rejected',
                "Your order #{$order->order_number} has been rejected."
            );
           try {
                app(FirebaseNotificationService::class)->sendToUser(
                    $order->customer_id,
                    'Order Rejected',
                    "Your order #{$order->order_number} has been rejected.",
                    [
                        'type' => 'order_status',
                        'order_id' => $order->id,
                        'status' => 'rejected',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $order->load(['items', 'customer', 'serviceArea']);
        });
    }

    public function requestOrder(int $adminId, int $orderId, ?string $note = null): Order
    {
        return DB::transaction(function () use ($adminId, $orderId, $note) {
            $order = Order::query()->where('id', $orderId)->lockForUpdate()->firstOrFail();

            if (!in_array($order->status, ['pending', 'approved'])) {
                throw new Exception('Only pending or approved orders can be requested.');
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => 'requested',
                'requested_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'changed_by' => $adminId,
                'old_status' => $oldStatus,
                'new_status' => 'requested',
                'note' => $note ?? 'Order marked as requested by admin.',
                'created_at' => now(),
            ]);

            // Send email notification
            // Mail::to($order->customer->email)->send(
            //     new OrderStatusNotification($order, 'requested', $note)
            // );

            // Log the email
            EmailLog::create([
                'order_id' => $order->id,
                'user_id' => $adminId,
                'email' => $order->customer->email,
                'subject' => "Your order #{$order->order_number} status update",
                'body' => view('emails.order_status_notification', [
                    'order' => $order,
                    'status' => 'requested',
                    'note' => $note,
                ])->render(),
                'sent_at' => now(),
            ]);
              $this->createCustomerNotification(
                $order,
                'Order Requested',
                "Your order #{$order->order_number} has been marked as requested."
            );
           try {
                app(FirebaseNotificationService::class)->sendToUser(
                    $order->customer_id,
                    'Order Requested',
                    "Your order #{$order->order_number} has been marked as requested.",
                    [
                        'type' => 'order_status',
                        'order_id' => $order->id,
                        'status' => 'requested',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $order->load(['items', 'customer']);
        });
    }
    
    public function updatePayment(int $adminId, int $orderId, array $data): Order
    {
        return DB::transaction(function () use ($adminId, $orderId, $data) {

            $order = Order::query()
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($order->status, ['approved','requested'])) {
                throw new \Exception('Only approved or requested orders can have payments updated.');
            }

            $note = $data['note'] ?? null;
            $paidAmount = 0;
            $lossAmount = 0;
            $lossReason = null;
            $lossStatus = 'none';
            $paidAt = null;
            $notPaidAt = null;
            //  $newOrderStatus = $order->status; // نحافظ على الحالة الحالية كقيمة افتراضية

            if ($data['payment_status'] === 'paid') {
                $paidAmount = (float) $data['paid_amount'];
                $lossAmount = max(0, $order->order_total - $paidAmount);
                $lossReason = $lossAmount > 0 ? $note : null; // general note if there is a loss
                $lossStatus = $lossAmount > 0 ? 'loss' : 'none';
                $paidAt = now();
                  // ✅ المنطق الذهبي: إذا كان الطلب قيد الطلب وتم الدفع، نغير حالته إلى مدفوع
            // if ($order->status === 'requested' || $order->status === 'approved') {
            //     $newOrderStatus = 'paid';
            // }
            } else { // not_paid
                $paidAmount = 0;
                $lossAmount = $order->order_total;
                $lossReason = $note; // always set note for not paid
                $lossStatus = 'loss';
                $notPaidAt = now();
                   // ✅ المنطق الذهبي: إذا كان الطلب قيد الطلب ولم يتم الدفع، نغير حالته إلى غير مدفوع
            // if ($order->status === 'requested' || $order->status === 'approved') {
            //     $newOrderStatus = 'not_paid';
            // }
            }

            // Update orders table
            $order->update([
                // 'status'=> $newOrderStatus, // الحالة الجديدة المحدثة

                'payment_status' => $data['payment_status'],
                'paid_amount' => $paidAmount,
                'loss_amount' => $lossAmount,
                'paid_at' => $paidAt,
                'not_paid_at' => $notPaidAt,
                'payment_updated_by' => $adminId,
                'admin_payment_note' => $note,
                'loss_reason' => $lossReason,
                'loss_status' => $lossStatus,
            ]);

            // Update or create Payment row
            \App\Models\Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'updated_by' => $adminId,
                    'payment_status' => $order->payment_status,
                    'amount' => $order->paid_amount,
                    'note' => $note,
                ]
            );

            // Update or create Loss row if there is a loss
            if ($lossAmount > 0) {
                \App\Models\Loss::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'recorded_by' => $adminId,
                        'amount' => $lossAmount,
                        'reason' => $lossReason,
                    ]
                );
            } else {
                // Delete loss row if fully paid
                \App\Models\Loss::where('order_id', $order->id)->delete();
            }

            return $order->refresh();
        });
    }
    public function listOrders(array $filters)
    {
        return Order::query()
            ->with(['items', 'customer'])
            ->when($filters['status'] ?? null, fn($q,$status) => $q->where('status',$status))
            ->when($filters['start_date'] ?? null, fn($q,$date) => $q->whereDate('created_at','>=',$date))
            ->when($filters['end_date'] ?? null, fn($q,$date) => $q->whereDate('created_at','<=',$date))
            ->orderBy('created_at','desc')
            ->paginate($filters['per_page'] ?? 15);
    }
    public function showOrder(int $orderId)
    {
        return Order::query()
            ->with([
                'items' => fn($q) => $q->select(
                    'id',
                    'order_id',
                    'item_name',
                    'item_price',
                    'quantity',
                    'line_total',
                    'customer_note'
                ),
                'customer' => fn($q) => $q->select(
                    'id',
                    'first_name',
                    'last_name',
                    'email', // optional if needed
                    'phone',
                    'birthday'
                ),
                'payments' => fn($q) => $q->select(
                    'id',
                    'order_id',
                    'payment_status',
                    'amount'
                ),
                'losses' => fn($q) => $q->select(
                    'id',
                    'order_id',
                    'amount',
                    'reason',
                    'recorded_by'
                ),
            ])
            ->with(['customer', 'items', 'restaurant']) // ✅ أضفنا restaurant هنا

            ->findOrFail($orderId)
            ->makeHidden(['service_area_id']); // hide service area if present
    }

     // Delete a single order if status is allowed
    public function deleteOrder(int $orderId): void
    {
        $order = Order::query()
            ->whereIn('status',['pending','cancelled','rejected','auto_rejected'])
            ->findOrFail($orderId);

        $order->delete();
    }

    // Delete all orders by a given status
    public function deleteOrdersByStatus(string $status): int
    {
        if (!in_array($status,['pending','cancelled','rejected','auto_rejected'])) {
            throw new Exception("Cannot delete orders with status '{$status}'.");
        }

        return Order::query()
            ->where('status',$status)
            ->delete();
    }
    // Payments with sum
    public function payments(array $filters): array
    {
        $query = Payment::query();

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $payments = $query->get(['id','order_id','payment_status','amount','note','updated_by','created_at']);
        $totalAmount = $payments->sum('amount');

        return [
            'payments' => $payments,
            'total_amount' => $totalAmount,
        ];
    }

    public function losses(array $filters): array
    {
        $query = Loss::query();

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $losses = $query->get(['id','order_id','amount','reason','recorded_by','created_at']);
        $totalLoss = $losses->sum('amount');

        return [
            'losses' => $losses,
            'total_loss_amount' => $totalLoss,
        ];
    }
}
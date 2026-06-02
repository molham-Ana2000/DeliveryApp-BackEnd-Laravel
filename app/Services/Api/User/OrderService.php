<?php
namespace App\Services\Api\User;

use App\Models\CustomerAddress;
use App\Models\EmailLog;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewOrderNotification;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;

class OrderService
{
    public function index(int $customerId, array $filters)
    {
        return Order::query()
            ->where('customer_id', $customerId)
      
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['from_date'] ?? null, function ($q, $date) {
                $q->whereDate('created_at', '>=', $date);
            })
            ->when($filters['to_date'] ?? null, function ($q, $date) {
                $q->whereDate('created_at', '<=', $date);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(int $customerId, array $data): Order
    {
        return DB::transaction(function () use ($customerId, $data) {
            $addressData = $this->resolveAddress($customerId, $data);

            $itemsTotal = 0;
            $preparedItems = [];

            foreach ($data['items'] as $itemData) {
                $menuItem = MenuItem::query()
                    ->where('id', $itemData['menu_item_id'])
                    ->where('restaurant_id', $data['restaurant_id'])
                    ->where('status', 'active')
                    ->firstOrFail();

                $quantity = $itemData['quantity'];
                $lineTotal = $menuItem->price * $quantity;
                $itemsTotal += $lineTotal;

                $preparedItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'item_price' => $menuItem->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'customer_note' => $itemData['customer_note'] ?? null,
                ];
            }

            $deliveryCost = 0;
            $orderTotal = $itemsTotal + $deliveryCost;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),

                'customer_id' => $customerId,
                'restaurant_id' => $data['restaurant_id'],

                'customer_address_id' => $addressData['customer_address_id'],
                'service_area_id' => $addressData['service_area_id'],

                'delivery_address' => $addressData['delivery_address'],
                'delivery_latitude' => $addressData['delivery_latitude'],
                'delivery_longitude' => $addressData['delivery_longitude'],

                'customer_note' => $data['customer_note'] ?? null,

                'status' => 'pending',
                'payment_status' => 'unpaid',
                'loss_status' => 'none',

                'items_total' => $itemsTotal,
                'delivery_cost' => $deliveryCost,
                'order_total' => $orderTotal,

                'pending_at' => now(),
                'expires_at' => now()->addMinutes(30),
            ]);

            $order->items()->createMany($preparedItems);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'changed_by' => $customerId,
                'old_status' => 'pending',
                'new_status' => 'pending',
                'note' => 'Order created by customer.',
                'created_at' => now(),
            ]);
             // ----------------------
        // Notify admin
        // ----------------------
        $admins = User::query()->where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NewOrderNotification($order));

            EmailLog::create([
                'order_id' => $order->id,
                'user_id' => $admin->id,
                'email' => $admin->email,
                'subject' => "New order #{$order->order_number} received",
                'body' => view('emails.new_order_notification', ['order' => $order])->render(),
                'sent_at' => now(),
            ]);

            Notification::create([
                'user_id' => $admin->id,
                'order_id' => $order->id,
                'title' => 'New Order Received',
                'body' => "New order #{$order->order_number} has been created.",
            ]);
        }

        // Send Firebase push to all admins ONLY ONCE
        try {
            app(FirebaseNotificationService::class)->sendToAdmins(
                'New Order Created',
                "New order #{$order->order_number} has been created.",
                [
                    'type' => 'new_order',
                    'order_id' => $order->id,
                    'status' => $order->status,
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
            return $order->load(['items']);
        });
    }

    public function show(int $customerId, int $orderId): Order
    {
        return Order::query()
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->with(['items', 'statusHistories'])
            ->firstOrFail();
    }

    public function updateOrder(int $customerId, int $orderId, array $data): Order
    {
        return DB::transaction(function () use ($customerId, $orderId, $data) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'pending') {
                throw new Exception('You cannot update this order because it has already been processed by admin.');
            }

            $restaurantId = $data['restaurant_id'] ?? $order->restaurant_id;

            $updateData = [
                'restaurant_id' => $restaurantId,
                'customer_note' => $data['customer_note'] ?? $order->customer_note,
            ];

            if (
                !empty($data['customer_address_id']) ||
                !empty($data['service_area_id'])
            ) {
                $addressData = $this->resolveAddress($customerId, $data);

                $updateData = array_merge($updateData, [
                    'customer_address_id' => $addressData['customer_address_id'],
                    'service_area_id' => $addressData['service_area_id'],
                    'delivery_address' => $addressData['delivery_address'],
                    'delivery_latitude' => $addressData['delivery_latitude'],
                    'delivery_longitude' => $addressData['delivery_longitude'],
                ]);
            }

            $order->update($updateData);

            if (!empty($data['items'])) {
                $itemsTotal = 0;
                $preparedItems = [];

                foreach ($data['items'] as $itemData) {
                    $menuItem = MenuItem::query()
                        ->where('id', $itemData['menu_item_id'])
                        ->where('restaurant_id', $restaurantId)
                        ->where('status', 'active')
                        ->firstOrFail();

                    $quantity = $itemData['quantity'];
                    $lineTotal = $menuItem->price * $quantity;
                    $itemsTotal += $lineTotal;

                    $preparedItems[] = [
                        'menu_item_id' => $menuItem->id,
                        'item_name' => $menuItem->name,
                        'item_price' => $menuItem->price,
                        'quantity' => $quantity,
                        'line_total' => $lineTotal,
                        'customer_note' => $itemData['customer_note'] ?? null,
                    ];
                }

                $order->items()->delete();
                $order->items()->createMany($preparedItems);

                $deliveryCost = $order->delivery_cost;
                $order->update([
                    'items_total' => $itemsTotal,
                    'order_total' => $itemsTotal + $deliveryCost,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'changed_by' => $customerId,
                'old_status' => $order->status,
                'new_status' => $order->status,
                'note' => 'Order updated by customer.',
                'created_at' => now(),
            ]);

            return $order->load(['items']);
        });
    }

    public function cancel(int $customerId, int $orderId, array $data): Order
    {
        return DB::transaction(function () use ($customerId, $orderId, $data) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'approved') {
                throw new Exception('You cannot cancel this order because it has already been approved by admin.');
            }

            if ($order->status !== 'pending') {
                throw new Exception('Only pending orders can be cancelled.');
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'changed_by' => $customerId,
                'old_status' => $oldStatus,
                'new_status' => 'cancelled',
                'note' => $data['note'] ?? 'Order cancelled by customer.',
                'created_at' => now(),
            ]);

            return $order->load(['items']);
        });
    }

    private function resolveAddress(int $customerId, array $data): array
    {
        if (!empty($data['customer_address_id'])) {
            $address = CustomerAddress::query()
                ->where('id', $data['customer_address_id'])
                ->where('user_id', $customerId)
                ->firstOrFail();

            if (!$address->service_area_id) {
                throw new Exception('This saved address is not linked to a service area.');
            }

            $serviceArea = ServiceArea::query()
                ->where('id', $address->service_area_id)
                ->where('is_active', true)
                ->firstOrFail();

            $this->validateLocationInsideServiceArea(
                $serviceArea,
                (float) $address->latitude,
                (float) $address->longitude
            );

            return [
                'customer_address_id' => $address->id,
                'service_area_id' => $address->service_area_id,
                'delivery_address' => $address->full_address,
                'delivery_latitude' => $address->latitude,
                'delivery_longitude' => $address->longitude,
            ];
        }

        $serviceArea = ServiceArea::query()
            ->where('id', $data['service_area_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $this->validateLocationInsideServiceArea(
            $serviceArea,
            (float) $data['delivery_latitude'],
            (float) $data['delivery_longitude']
        );

        return [
            'customer_address_id' => null,
            'service_area_id' => $serviceArea->id,
            'delivery_address' => $data['delivery_address'],
            'delivery_latitude' => $data['delivery_latitude'],
            'delivery_longitude' => $data['delivery_longitude'],
        ];
    }

    private function validateLocationInsideServiceArea(ServiceArea $serviceArea,float $latitude,float $longitude): void 
    {
        if (empty($serviceArea->polygon)) {
            throw new Exception('Service area polygon is missing.');
        }

        $polygon = is_string($serviceArea->polygon)
            ? json_decode($serviceArea->polygon, true)
            : $serviceArea->polygon;

        if (!is_array($polygon) || count($polygon) < 3) {
            throw new Exception('Service area polygon is invalid.');
        }

        $isInside = $this->pointInPolygon(
            $latitude,
            $longitude,
            $polygon
        );

        if (!$isInside) {
            throw new Exception('The selected location is outside the service area.');
        }
    }
    private function pointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $pointI = $polygon[$i];
            $pointJ = $polygon[$j];

            $latI = (float) $pointI['lat'];
            $lngI = (float) $pointI['lng'];

            $latJ = (float) $pointJ['lat'];
            $lngJ = (float) $pointJ['lng'];

            $intersect = (($lngI > $longitude) !== ($lngJ > $longitude))
                && ($latitude < ($latJ - $latI) * ($longitude - $lngI) / ($lngJ - $lngI) + $latI);

            if ($intersect) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }

    private function calculateDistanceKm(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371;

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($lngDiff / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
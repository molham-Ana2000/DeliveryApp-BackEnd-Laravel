<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateOrderRequest;
use App\Http\Requests\User\OrderFilterRequest;
use App\Http\Requests\User\UpdateOrderStatusRequest;
use App\Http\Requests\User\UpdateOrderRequest;

use App\Models\Order;
use App\Services\Api\User\OrderService;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(OrderFilterRequest $request): JsonResponse
    {
        try {
            $orders = $this->orderService->index(
                $request->user()->id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load orders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->create(
                $request->user()->id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
     public function editData(Order $order)
    {
        $data = $this->orderService->getEditData(
            auth()->id(),
            $order
        );

        return response()->json([
            'success' => true,
            'message' => 'Order edit data retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->show(
                auth()->id(),
                $order->id
            );

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function cancel(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->cancel(
                $request->user()->id,
                $order->id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => $order,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder(
                $request->user()->id,
                $order->id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'data' => $order,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
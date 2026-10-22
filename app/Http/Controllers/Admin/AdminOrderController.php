<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:pending,processing,completed,cancelled'],
            'payment_status' => ['sometimes', 'nullable', 'in:unpaid,paid,failed,refunded'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->with(['user:id,name,email', 'items.product'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(
                $filters['payment_status'] ?? null,
                fn ($query, string $status) => $query->where('payment_status', $status)
            )
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        if (! $request->expectsJson()) {
            return view('admin.orders.index', compact('orders', 'filters'));
        }

        return response()->json(['data' => $orders]);
    }

    public function show(Order $order): JsonResponse|View
    {
        $order->load(['user', 'items.product.category', 'payment']);

        if (! request()->expectsJson()) {
            return view('admin.orders.show', compact('order'));
        }

        return response()->json(['data' => $order]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderService $orders
    ): JsonResponse|RedirectResponse {
        $data = $request->validated();

        $updatedOrder = $orders->updateStatus($order, $data['status'], $data['shipping_status'] ?? null);

        if (! $request->expectsJson()) {
            return back()->with('success', 'Status pesanan diperbarui.');
        }

        return response()->json([
            'message' => 'Order status updated.',
            'data' => $updatedOrder,
        ]);
    }
}

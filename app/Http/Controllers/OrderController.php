<?php

namespace App\Http\Controllers;

use App\Enums\PaymentChannel;
use App\Exceptions\MidtransUnavailableException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        /** @var User $user */
        $user = $request->user();
        $orders = $user->orders()
            ->with(['items.product', 'payment'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        if (! $request->expectsJson()) {
            return view('shop.orders.index', compact('orders'));
        }

        return response()->json(['data' => $orders]);
    }

    public function create(Request $request, CartService $carts): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();
        $cart = $carts->getForUser($user);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return view('shop.checkout.create', compact('cart'));
    }

    public function store(StoreOrderRequest $request, OrderService $orders, PaymentService $payments): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $order = $orders->createFromCart($user, $request->validated());
        $paymentError = null;
        if ($order->payment_channel === PaymentChannel::MIDTRANS) {
            try {
                $order = $payments->startSession($order);
            } catch (MidtransUnavailableException) {
                $paymentError = 'Pesanan tersimpan. Sesi pembayaran belum tersedia; coba lagi dari halaman pesanan.';
            }
        }

        if (! $request->expectsJson()) {
            return redirect()->route('orders.show', $order)->with('success', $paymentError ?? 'Pesanan berhasil dibuat.');
        }

        return response()->json([
            'message' => 'Order submitted successfully.',
            'payment_error' => $paymentError,
            'data' => $order,
        ], 201);
    }

    public function show(Order $order): JsonResponse|View
    {
        $this->authorize('view', $order);

        $order->load(['items.product.category', 'user', 'payment', 'paymentProofs']);

        if (! request()->expectsJson()) {
            return view('shop.orders.show', compact('order'));
        }

        return response()->json(['data' => $order]);
    }
}

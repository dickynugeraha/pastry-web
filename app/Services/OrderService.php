<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromCart(User $user, array $data): Order
    {
        if (! in_array($data['delivery_method'] ?? null, ['delivery', 'pickup'], true)) {
            throw ValidationException::withMessages([
                'delivery_method' => 'The selected delivery method is invalid.',
            ]);
        }

        return DB::transaction(function () use ($user, $data): Order {
            $cart = Cart::query()
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            if ($cart === null) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $cartItems = $cart->items()->orderBy('id')->lockForUpdate()->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $products = [];
            foreach ($cartItems as $cartItem) {
                $product = Product::query()
                    ->whereKey($cartItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product === null || ! $product->is_active || $cartItem->qty > $product->stock) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products are no longer available in the requested quantity.',
                    ]);
                }

                $product->load('images');
                $products[$product->id] = $product;
            }

            $total = 0.0;
            foreach ($cartItems as $cartItem) {
                $total += (float) $products[$cartItem->product_id]->price * $cartItem->qty;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => $this->uniqueOrderCode(),
                'total' => round($total, 2),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_channel' => $data['payment_channel'],
                'manual_payment_method' => $data['payment_channel'] === 'manual' ? $data['manual_payment_method'] : null,
                'manual_payment_details' => $data['payment_channel'] === 'manual' ? config('payments.manual.'.$data['manual_payment_method']) : null,
                'midtrans_order_id' => $data['payment_channel'] === 'midtrans' ? 'BK-'.Str::uuid() : null,
                'payment_due_at' => now()->addMinutes(config('payments.midtrans.expiry_minutes')),
                'delivery_method' => $data['delivery_method'],
                'shipping_status' => 'pending',
                'recipient_name' => $data['recipient_name'] ?? $user->name,
                'recipient_phone' => $data['recipient_phone'] ?? $user->phone,
                'shipping_address' => $data['shipping_address'] ?? null,
                'pickup_code' => $data['delivery_method'] === 'pickup' ? $this->uniquePickupCode() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cartItems as $cartItem) {
                $product = $products[$cartItem->product_id];
                $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                $price = (float) $product->price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'qty' => $cartItem->qty,
                    'price' => $price,
                    'subtotal' => round($price * $cartItem->qty, 2),
                    'product_name_snapshot' => $product->name,
                    'product_image_snapshot' => $image?->path,
                ]);

                $product->decrement('stock', $cartItem->qty);
            }

            $cart->items()->delete();

            return $order->load(['user', 'items.product']);
        });
    }

    public function updateStatus(Order $order, string $status, ?string $shippingStatus = null): Order
    {
        if (! in_array($status, ['pending', 'processing', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'The selected order status is invalid.',
            ]);
        }

        if ($shippingStatus !== null && ! in_array($shippingStatus, [
            'pending',
            'processing',
            'delivering',
            'ready_for_pickup',
            'completed',
            'cancelled',
        ], true)) {
            throw ValidationException::withMessages([
                'shipping_status' => 'The selected shipping status is invalid.',
            ]);
        }

        return DB::transaction(function () use ($order, $status, $shippingStatus): Order {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (($status === 'cancelled' || $shippingStatus === 'cancelled') && $lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages(['status' => 'Pesanan yang sudah dibayar memerlukan refund sebelum dibatalkan.']);
            }
            if ($shippingStatus === 'cancelled' && $status !== 'cancelled') {
                throw ValidationException::withMessages(['shipping_status' => 'Batalkan pesanan untuk membatalkan pengiriman.']);
            }
            if ($status === 'cancelled' && $lockedOrder->payment_channel === PaymentChannel::MIDTRANS && $lockedOrder->status !== 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Sinkronkan pembatalan atau kedaluwarsa melalui Midtrans.']);
            }
            if (($status === 'processing' || $status === 'completed' || in_array($shippingStatus, ['processing', 'delivering', 'ready_for_pickup', 'completed'], true)) && $lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Pembayaran harus sah sebelum pesanan diproses.']);
            }
            if ($lockedOrder->status === 'completed' && $status !== 'completed') {
                throw ValidationException::withMessages(['status' => 'Pesanan selesai tidak dapat dibuka kembali.']);
            }

            if ($lockedOrder->status === 'cancelled' && $status !== 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => 'A cancelled order cannot be reopened.',
                ]);
            }

            if ($status === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                foreach ($lockedOrder->items()->orderBy('product_id')->get() as $item) {
                    if ($item->product_id !== null) {
                        Product::query()
                            ->whereKey($item->product_id)
                            ->lockForUpdate()
                            ->increment('stock', $item->qty);
                    }
                }
            }

            $attributes = ['status' => $status];

            if ($status === 'completed') {
                $attributes['shipping_status'] = 'completed';
                $attributes['completed_at'] = now();
            } elseif ($status === 'cancelled') {
                $attributes['shipping_status'] = 'cancelled';
            } elseif ($shippingStatus !== null) {
                $attributes['shipping_status'] = $shippingStatus;
            }

            $lockedOrder->update($attributes);

            return $lockedOrder->refresh()->load(['user', 'items.product']);
        });
    }

    private function uniqueOrderCode(): string
    {
        do {
            $code = 'BK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
        } while (Order::query()->where('order_code', $code)->exists());

        return $code;
    }

    private function uniquePickupCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Order::query()->where('pickup_code', $code)->exists());

        return $code;
    }
}

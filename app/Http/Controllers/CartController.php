<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(Request $request, CartService $carts): JsonResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        $cart = $carts->getForUser($user);

        if (! $request->expectsJson()) {
            return view('shop.cart.show', compact('cart'));
        }

        return response()->json(['data' => $this->payload($cart)]);
    }

    public function store(
        StoreCartItemRequest $request,
        Product $product,
        CartService $carts
    ): JsonResponse|RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $item = $carts->add($user, $product, $request->integer('quantity'));

        if (! $request->expectsJson()) {
            return redirect()->route('cart.show')->with('success', 'Produk ditambahkan ke keranjang.');
        }

        return response()->json([
            'message' => 'Product added to cart.',
            'data' => $item->load('product'),
        ], 201);
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $cartItem,
        CartService $carts
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $cartItem);
        /** @var User $user */
        $user = $request->user();

        $item = $carts->updateQuantity($user, $cartItem, $request->integer('quantity'))->load('product');

        if (! $request->expectsJson()) {
            return back()->with('success', 'Jumlah produk diperbarui.');
        }

        return response()->json([
            'message' => 'Cart updated.',
            'data' => $item,
        ]);
    }

    public function destroy(
        Request $request,
        CartItem $cartItem,
        CartService $carts
    ): JsonResponse|RedirectResponse {
        $this->authorize('delete', $cartItem);
        /** @var User $user */
        $user = $request->user();
        $carts->remove($user, $cartItem);

        if (! $request->expectsJson()) {
            return back()->with('success', 'Produk dihapus dari keranjang.');
        }

        return response()->json(['message' => 'Product removed from cart.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'items' => $cart->items,
            'total' => $cart->total,
            'items_count' => $cart->items->sum('qty'),
        ];
    }
}

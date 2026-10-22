<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * @param  array<int, int>  $productIds
     * @return array<int, int>
     */
    public function quantitiesForProducts(?User $user, array $productIds): array
    {
        if ($user === null || $productIds === []) {
            return [];
        }

        return CartItem::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('cart', fn (Builder $query): Builder => $query->whereBelongsTo($user))
            ->pluck('qty', 'product_id')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();
    }

    public function getForUser(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id])
            ->load(['items.product.category', 'items.product.images']);
    }

    public function add(User $user, Product $product, int $quantity): CartItem
    {
        $this->ensureValidQuantity($quantity);

        return DB::transaction(function () use ($user, $product, $quantity): CartItem {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePurchasable($lockedProduct);

            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $item = CartItem::query()
                ->whereBelongsTo($cart)
                ->whereBelongsTo($lockedProduct, 'product')
                ->lockForUpdate()
                ->first();
            $newQuantity = ($item?->qty ?? 0) + $quantity;

            if ($newQuantity > $lockedProduct->stock) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$lockedProduct->stock} units of {$lockedProduct->name} are available.",
                ]);
            }

            if ($item === null) {
                return CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $lockedProduct->id,
                    'qty' => $newQuantity,
                    'price_snapshot' => $lockedProduct->price,
                ]);
            }

            $item->update([
                'qty' => $newQuantity,
                'price_snapshot' => $lockedProduct->price,
            ]);

            return $item->refresh();
        });
    }

    public function updateQuantity(User $user, CartItem $item, int $quantity): CartItem
    {
        $this->ensureValidQuantity($quantity);

        return DB::transaction(function () use ($user, $item, $quantity): CartItem {
            $cart = Cart::query()
                ->whereBelongsTo($user)
                ->whereKey($item->cart_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedItem = CartItem::query()
                ->whereBelongsTo($cart)
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedProduct = Product::query()
                ->whereKey($lockedItem->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePurchasable($lockedProduct);

            if ($quantity > $lockedProduct->stock) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$lockedProduct->stock} units of {$lockedProduct->name} are available.",
                ]);
            }

            $lockedItem->update([
                'qty' => $quantity,
                'price_snapshot' => $lockedProduct->price,
            ]);

            return $lockedItem->refresh();
        });
    }

    public function remove(User $user, CartItem $item): void
    {
        $cart = Cart::query()
            ->whereBelongsTo($user)
            ->whereKey($item->cart_id)
            ->firstOrFail();

        CartItem::query()
            ->whereBelongsTo($cart)
            ->whereKey($item->id)
            ->delete();
    }

    private function ensurePurchasable(Product $product): void
    {
        if (! $product->is_active || $product->stock < 1) {
            throw ValidationException::withMessages([
                'product' => 'This product is no longer available.',
            ]);
        }
    }

    private function ensureValidQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 100) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be between 1 and 100.',
            ]);
        }
    }
}

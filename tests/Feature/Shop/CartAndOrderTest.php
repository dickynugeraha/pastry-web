<?php

namespace Tests\Feature\Shop;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_adding_a_product(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->post(route('cart-items.store', $product), ['quantity' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_product_picker_shows_remaining_stock_and_saving_redirects_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 2])
            ->assertCreated();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('name="quantity" min="1" max="3"', false)
            ->assertSeeText('3 unit tersedia untuk ditambahkan')
            ->assertSeeText('Simpan');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('name="quantity" min="1" max="3"', false);

        $this->post(route('cart-items.store', $product), ['quantity' => 3])
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'qty' => 5,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSeeText('Seluruh stok produk ini sudah ada di keranjang Anda.')
            ->assertDontSee('name="quantity"', false);
    }

    public function test_product_picker_caps_each_addition_at_one_hundred(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 120]);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('name="quantity" min="1" max="100"', false)
            ->assertSeeText('Maksimal 100 unit setiap kali menyimpan.');
    }

    public function test_customer_can_submit_cart_as_order_and_stock_is_decremented(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 25000, 'stock' => 5]);

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 2])
            ->assertCreated();

        $cartItem = CartItem::query()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('cart-items.update', $cartItem), ['quantity' => 3])
            ->assertOk();

        $response = $this->actingAs($user)->postJson(route('orders.store'), [
            'delivery_method' => 'delivery',
            'recipient_name' => 'Customer Bunkue',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Jl. Pastry No. 1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.product_id', $product->id);

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 3,
            'subtotal' => 75000,
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_customer_cannot_change_another_customers_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $cartItem = CartItem::factory()->create([
            'cart_id' => $owner->cart()->create()->id,
        ]);

        $this->actingAs($otherUser)
            ->patchJson(route('cart-items.update', $cartItem), ['quantity' => 2])
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'qty' => 1]);
    }

    public function test_customer_can_update_cart_quantity_from_the_browser_form(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 1])
            ->assertCreated();
        $cartItem = CartItem::query()->firstOrFail();

        $this->actingAs($user)
            ->from(route('cart.show'))
            ->patch(route('cart-items.update', $cartItem), ['quantity' => 3])
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'qty' => 3]);
    }

    public function test_checkout_redirects_an_empty_cart_and_shows_a_filled_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Almond Tart', 'price' => 25000, 'stock' => 5]);

        $this->actingAs($user)
            ->get(route('checkout.create'))
            ->assertRedirect(route('cart.show'));

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 2])
            ->assertCreated();

        $this->actingAs($user)
            ->get(route('checkout.create'))
            ->assertOk()
            ->assertSeeText('Almond Tart')
            ->assertSeeText('Rp 50.000')
            ->assertSeeText('Cara menerima pesanan');
    }

    public function test_customer_order_detail_shows_item_prices_and_pickup_code(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'total' => 30000,
            'delivery_method' => 'pickup',
            'pickup_code' => 'BK123456',
        ]);
        OrderItem::factory()->for($order)->create([
            'product_name_snapshot' => 'Almond Tart',
            'qty' => 2,
            'price' => 15000,
            'subtotal' => 30000,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSeeText('Almond Tart')
            ->assertSeeText('2 × Rp 15.000')
            ->assertSeeText('BK123456');
    }

    public function test_cart_quantity_validation_rejects_zero_and_excessive_quantities(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 200]);

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_delivery_order_requires_recipient_details(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->postJson(route('cart-items.store', $product), ['quantity' => 1])
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('orders.store'), [
                'delivery_method' => 'delivery',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'recipient_name',
                'recipient_phone',
                'shipping_address',
            ]);
    }
}

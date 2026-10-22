<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcommerceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_forbidden_from_admin_endpoints(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->getJson(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_customer_is_redirected_from_admin_browser_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('products.index'));
    }

    public function test_admin_can_create_and_update_a_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Chocolate Croissant',
            'description' => 'Freshly baked pastry',
            'price' => 28000,
            'stock' => 10,
        ]);

        $response->assertCreated()->assertJsonPath('data.slug', 'chocolate-croissant');
        $product = Product::query()->firstOrFail();

        $this->actingAs($admin)
            ->patchJson(route('admin.products.update', $product), ['stock' => 20])
            ->assertOk()
            ->assertJsonPath('data.stock', 20);
    }

    public function test_admin_can_upload_product_images_that_are_shown_to_customers(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Image Croissant',
            'price' => 28000,
            'stock' => 10,
            'images' => [UploadedFile::fake()->image('croissant.jpg', 1200, 800)],
        ]);

        $product = Product::query()->with('images')->firstOrFail();
        $image = $product->images->sole();

        $response->assertRedirect(route('admin.products.show', $product));
        $this->assertTrue($image->is_primary);
        Storage::disk('public')->assertExists($image->path);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee($product->primary_image_url);
    }

    public function test_product_photo_picker_renders_on_create_and_edit_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $product->images()->create(['path' => 'products/existing.jpg', 'is_primary' => true]);

        $this->actingAs($admin)->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Tambah Foto')
            ->assertSee('productPhotoPicker');

        $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Jadikan utama')
            ->assertSee('existing.jpg');
    }

    public function test_admin_product_detail_renders_a_preview_gallery_for_multiple_photos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Kue Cokelat']);
        $product->images()->create(['path' => 'products/front.jpg', 'is_primary' => true]);
        $product->images()->create(['path' => 'products/side.jpg', 'sort_order' => 1]);

        $this->actingAs($admin)->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('products/front.jpg')
            ->assertSee('products/side.jpg')
            ->assertSee('Foto utama')
            ->assertSee('aria-modal="true"', false);
    }

    public function test_admin_can_replace_product_photos_and_delete_their_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $first = $product->images()->create(['path' => 'products/first.jpg', 'is_primary' => true]);
        $second = $product->images()->create(['path' => 'products/second.jpg', 'sort_order' => 1]);
        Storage::disk('public')->put($first->path, 'first');
        Storage::disk('public')->put($second->path, 'second');

        $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'remove_image_ids' => [$first->id],
            'primary_image_id' => $second->id,
            'images' => [UploadedFile::fake()->image('new.jpg')],
        ])->assertRedirect(route('admin.products.show', $product));

        $photos = $product->fresh()->images;
        $this->assertCount(2, $photos);
        $this->assertTrue($photos->firstWhere('id', $second->id)->is_primary);
        Storage::disk('public')->assertMissing($first->path);
        Storage::disk('public')->assertExists($second->path);
        Storage::disk('public')->assertExists($photos->last()->path);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        Storage::disk('public')->assertMissing($second->path);
        Storage::disk('public')->assertMissing($photos->last()->path);
    }

    public function test_admin_cannot_upload_more_than_five_product_photos_in_total(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        foreach (range(1, 5) as $index) {
            $product->images()->create(['path' => "products/{$index}.jpg", 'sort_order' => $index]);
        }

        $this->actingAs($admin)->withHeaders(['Accept' => 'application/json'])
            ->patch(route('admin.products.update', $product), [
                'images' => [UploadedFile::fake()->image('extra.jpg')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images']);

        $this->assertCount(5, $product->fresh()->images);
    }

    public function test_product_create_and_update_reject_invalid_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Unsafe Product',
                'price' => -1,
                'stock' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->patchJson(route('admin.products.update', $product), ['price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_customer_cannot_create_an_admin_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->postJson(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Blocked Product',
                'price' => 10000,
                'stock' => 1,
            ])
            ->assertForbidden();
    }

    public function test_admin_user_create_and_update_reject_weak_passwords(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.users.store'), [
                'name' => 'New Customer',
                'email' => 'new-customer@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
                'role' => 'customer',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->patchJson(route('admin.users.update', $customer), [
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_shipping_status_must_match_the_order_delivery_method(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create([
            'delivery_method' => 'pickup',
            'shipping_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.status', $order), [
                'status' => 'processing',
                'shipping_status' => 'delivering',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['shipping_status']);
    }

    public function test_admin_order_detail_shows_item_price_and_status_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['total' => 30000]);
        OrderItem::factory()->for($order)->create([
            'product_name_snapshot' => 'Almond Tart',
            'qty' => 2,
            'price' => 15000,
            'subtotal' => 30000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSeeText('2 × Rp 15.000')
            ->assertSeeText('Status pengiriman');
    }

    public function test_admin_dashboard_returns_period_summary_and_order_report_download(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 15000]);
        $order = Order::factory()->for($customer)->create([
            'total' => 30000,
            'created_at' => now(),
        ]);
        OrderItem::factory()->for($order)->for($product)->create([
            'qty' => 2,
            'price' => 15000,
            'subtotal' => 30000,
            'product_name_snapshot' => $product->name,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.dashboard', ['period' => 'day']))
            ->assertOk()
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonPath('data.total_revenue', 30000);

        $this->actingAs($admin)
            ->get(route('admin.reports.orders.download', ['period' => 'day']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_sales_chart_and_report_downloads_follow_the_selected_period(): void
    {
        $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));
        $admin = User::factory()->create(['role' => 'admin']);
        $sale = Order::factory()->create([
            'order_code' => 'BK-20261020100000-ABCDE',
            'total' => 30000,
            'created_at' => '2026-10-20 10:00:00',
        ]);
        $cancelled = Order::factory()->create([
            'order_code' => 'BK-CANCELLED',
            'status' => 'cancelled',
            'total' => 10000,
            'created_at' => '2026-10-21 10:00:00',
        ]);
        OrderItem::factory()->for($sale)->create(['product_name_snapshot' => 'Kue Aktif']);
        OrderItem::factory()->for($cancelled)->create(['product_name_snapshot' => 'Kue Batal']);
        Order::factory()->create([
            'order_code' => 'BK-OUTSIDE',
            'total' => 90000,
            'created_at' => '2026-10-19 10:00:00',
        ]);
        $filters = ['period' => 'custom', 'start_date' => '2026-10-20', 'end_date' => '2026-10-21'];

        $this->actingAs($admin)->getJson(route('admin.dashboard', $filters))
            ->assertOk()
            ->assertJsonPath('data.total_orders', 2)
            ->assertJsonPath('data.total_revenue', 30000)
            ->assertJsonPath('data.sales_chart.0.revenue', 30000)
            ->assertJsonPath('data.sales_chart.1.revenue', 0)
            ->assertJsonPath('data.top_products.0.product', 'Kue Aktif');

        $this->actingAs($admin)->get(route('admin.reports.orders', $filters))
            ->assertOk()
            ->assertSeeText('Total pendapatan')
            ->assertSeeText('Rp 30.000')
            ->assertSee('Unduh PDF');

        $csv = $this->actingAs($admin)->get(route('admin.reports.orders.download', $filters));
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Total Pendapatan', $csvContent);
        $this->assertStringNotContainsString('BK-OUTSIDE', $csvContent);

        $pdf = $this->actingAs($admin)->get(route('admin.reports.orders.download.pdf', $filters));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        $this->assertStringContainsString('BK-20261020100000-ABCDE', $pdf->getContent());
        $this->assertStringContainsString('Rp 30.000', $pdf->getContent());
        $this->assertStringNotContainsString('BK-OUTSIDE', $pdf->getContent());
    }

    public function test_pdf_report_has_a_second_page_for_many_orders(): void
    {
        $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));
        $admin = User::factory()->create(['role' => 'admin']);
        Order::factory()->count(22)->create(['created_at' => '2026-10-22 10:00:00']);

        $response = $this->actingAs($admin)->get(route('admin.reports.orders.download.pdf', ['period' => 'day']));

        $response->assertOk();
        $this->assertStringContainsString('/Count 2', $response->getContent());
        $this->assertStringContainsString('Halaman 2 / 2', $response->getContent());
    }

    public function test_pdf_report_shows_an_empty_period_without_orders(): void
    {
        $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.orders.download.pdf', ['period' => 'day']));

        $response->assertOk();
        $this->assertStringContainsString('Belum ada pesanan pada periode ini.', $response->getContent());
        $this->assertStringContainsString('Total pesanan', $response->getContent());
    }
}

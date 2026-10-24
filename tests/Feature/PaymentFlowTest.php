<?php

namespace Tests\Feature;

use App\Exceptions\MidtransUnavailableException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\PaymentStatusLog;
use App\Models\Product;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.midtrans.enabled' => true, 'payments.midtrans.server_key' => 'test-server-key', 'payments.midtrans.client_key' => 'test-client-key', 'payments.midtrans.merchant_id' => 'test-merchant']);
    }

    #[TestWith(['bca'])]
    #[TestWith(['dana'])]
    #[TestWith(['gopay'])]
    public function test_manual_checkout_snapshots_destination_without_calling_midtrans(string $method): void
    {
        $this->mock(MidtransService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('createSession'));
        [$user, $product] = $this->cart();

        $this->actingAs($user)->postJson(route('orders.store'), ['delivery_method' => 'pickup', 'payment_channel' => 'manual', 'manual_payment_method' => $method, 'manual_payment_details' => ['destination_number' => 'attacker']])->assertCreated()->assertJsonPath('data.manual_payment_details.destination_number', '12345');

        $order = Order::query()->sole();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'manual_payment_method' => $method, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseCount('cart_items', 0);
        config(['payments.manual.'.$method.'.destination_number' => 'changed']);
        $this->get(route('orders.show', $order))->assertOk()->assertSeeText('12345')->assertDontSeeText('changed');
    }

    #[TestWith([[], 'payment_channel'])]
    #[TestWith([['payment_channel' => 'manual'], 'manual_payment_method'])]
    #[TestWith([['payment_channel' => 'manual', 'manual_payment_method' => 'invalid'], 'manual_payment_method'])]
    #[TestWith([['payment_channel' => 'midtrans', 'manual_payment_method' => 'bca'], 'manual_payment_method'])]
    public function test_invalid_checkout_preserves_cart_and_stock(array $data, string $field): void
    {
        [$user, $product] = $this->cart();

        $this->actingAs($user)->postJson(route('orders.store'), ['delivery_method' => 'pickup', ...$data])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_disabled_midtrans_rejects_checkout(): void
    {
        config(['payments.midtrans.enabled' => false]);
        [$user] = $this->cart();

        $this->actingAs($user)->postJson(route('orders.store'), ['delivery_method' => 'pickup', 'payment_channel' => 'midtrans'])->assertUnprocessable()->assertJsonValidationErrors('payment_channel');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_failed_snap_creation_can_retry_without_duplicate_order_or_stock_changes(): void
    {
        $this->mock(MidtransService::class, fn (MockInterface $mock) => $mock->shouldReceive('createSession')->once()->andThrow(new MidtransUnavailableException('unavailable')));
        [$user, $product] = $this->cart();
        $this->actingAs($user)->postJson(route('orders.store'), ['delivery_method' => 'pickup', 'payment_channel' => 'midtrans'])->assertCreated()->assertJsonPath('data.snap_token', null);
        $order = Order::query()->sole();
        $externalId = $order->midtrans_order_id;
        $this->mock(MidtransService::class, fn (MockInterface $mock) => $mock->shouldReceive('createSession')->once()->andReturn('snap-token'));

        $this->postJson(route('orders.midtrans.session', $order))->assertOk()->assertJsonPath('token', 'snap-token');
        $this->postJson(route('orders.midtrans.session', $order))->assertOk()->assertJsonPath('token', 'snap-token');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'midtrans_order_id' => $externalId, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    }

    public function test_owner_uploads_private_proof_without_marking_order_paid(): void
    {
        Storage::fake('local');
        $order = Order::factory()->manual()->create();

        $this->actingAs($order->user)->postJson(route('orders.payment-proofs.store', $order), ['proof' => UploadedFile::fake()->image('receipt.png')])->assertCreated()->assertJsonMissingPath('data.file_path');

        $proof = PaymentProof::query()->sole();
        Storage::disk('local')->assertExists($proof->file_path);
        $this->assertStringStartsWith('payment-proofs/'.$order->id.'/', $proof->file_path);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'unpaid']);
        $this->get(route('orders.payment-proofs.show', [$order, $proof]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Type', 'image/png');
    }

    public function test_admin_downloads_pdf_as_attachment(): void
    {
        Storage::fake('local');
        $order = Order::factory()->manual()->create();
        $file = UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $this->actingAs($order->user)->postJson(route('orders.payment-proofs.store', $order), ['proof' => $file])->assertCreated();
        $proof = PaymentProof::query()->sole();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.orders.payment-proofs.show', [$order, $proof]))->assertOk()->assertDownload('bukti-'.$proof->id.'.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_other_customer_cannot_upload_or_read_proof(): void
    {
        $proof = PaymentProof::factory()->create();

        $this->actingAs(User::factory()->create())->postJson(route('orders.payment-proofs.store', $proof->order), ['proof' => UploadedFile::fake()->image('receipt.jpg')])->assertForbidden();
        $this->get(route('orders.payment-proofs.show', [$proof->order, $proof]))->assertForbidden();

        $this->assertDatabaseCount('payment_proofs', 1);
    }

    public function test_nested_binding_rejects_proof_from_another_order(): void
    {
        $order = Order::factory()->manual()->create();
        $proof = PaymentProof::factory()->create();

        $this->actingAs($order->user)->get(route('orders.payment-proofs.show', [$order, $proof]))->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson(route('admin.orders.payment-proofs.update', [$order, $proof]), ['action' => 'accept'])->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $proof->order_id, 'payment_status' => 'unpaid']);
    }

    #[TestWith(['mime'])]
    #[TestWith(['extension'])]
    #[TestWith(['size'])]
    public function test_invalid_proof_files_are_rejected(string $case): void
    {
        Storage::fake('local');
        $order = Order::factory()->manual()->create();
        $unsafeSource = UploadedFile::fake()->createWithContent('receipt.jpg', '<script>alert(1)</script>');
        $file = match ($case) {
            'mime' => new UploadedFile($unsafeSource->getPathname(), 'receipt.jpg', 'image/jpeg', null, true),
            'extension' => UploadedFile::fake()->image('receipt.php'),
            'size' => UploadedFile::fake()->image('receipt.png')->size(5121),
        };

        $this->actingAs($order->user)->postJson(route('orders.payment-proofs.store', $order), ['proof' => $file])->assertUnprocessable()->assertJsonValidationErrors('proof');

        $this->assertDatabaseCount('payment_proofs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_paid_and_midtrans_orders_reject_proof_upload(): void
    {
        Storage::fake('local');
        $order = Order::factory()->manual()->paid()->create();
        $this->actingAs($order->user)->postJson(route('orders.payment-proofs.store', $order), ['proof' => UploadedFile::fake()->image('receipt.jpg')])->assertUnprocessable();
        $midtrans = Order::factory()->for($order->user)->midtrans()->create();

        $this->postJson(route('orders.payment-proofs.store', $midtrans), ['proof' => UploadedFile::fake()->image('receipt.jpg')])->assertUnprocessable();

        $this->assertDatabaseCount('payment_proofs', 0);
    }

    public function test_admin_accepts_proof_once_with_payment_and_audit_record(): void
    {
        $proof = PaymentProof::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $other = PaymentProof::factory()->for($proof->order)->create();
        $url = route('admin.orders.payment-proofs.update', [$proof->order, $proof]);

        $this->actingAs($admin)->patchJson($url, ['action' => 'accept'])->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $proof->order_id, 'payment_status' => 'paid', 'status' => 'pending']);
        $this->assertNotNull($proof->order->refresh()->paid_at);
        $this->assertDatabaseHas('payment_proofs', ['id' => $other->id, 'review_status' => 'superseded']);
        $this->assertDatabaseHas('payments', ['order_id' => $proof->order_id, 'provider' => 'manual', 'confirmed_by_user_id' => $admin->id]);
        $this->assertDatabaseHas('payment_status_logs', ['order_id' => $proof->order_id, 'source' => 'manual_proof', 'changed_by_user_id' => $admin->id, 'from_status' => 'unpaid', 'to_status' => 'paid']);
        $this->patchJson($url, ['action' => 'accept'])->assertUnprocessable();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_admin_rejection_requires_reason_and_allows_resubmission(): void
    {
        Storage::fake('local');
        $proof = PaymentProof::factory()->create();
        $url = route('admin.orders.payment-proofs.update', [$proof->order, $proof]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson($url, ['action' => 'reject'])->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');

        $this->patchJson($url, ['action' => 'reject', 'rejection_reason' => 'Nominal tidak sesuai.'])->assertOk();

        $this->assertDatabaseHas('payment_proofs', ['id' => $proof->id, 'review_status' => 'rejected', 'rejection_reason' => 'Nominal tidak sesuai.']);
        $this->assertDatabaseHas('orders', ['id' => $proof->order_id, 'payment_status' => 'unpaid']);
        $this->actingAs($proof->order->user)->postJson(route('orders.payment-proofs.store', $proof->order), ['proof' => UploadedFile::fake()->image('new.jpg')])->assertCreated();
        $this->assertDatabaseCount('payment_proofs', 2);
    }

    public function test_customer_cannot_review_or_override_payments(): void
    {
        $proof = PaymentProof::factory()->create();

        $this->actingAs($proof->order->user)->patchJson(route('admin.orders.payment-proofs.update', [$proof->order, $proof]), ['action' => 'accept'])->assertForbidden();
        $this->patchJson(route('admin.orders.manual-payment.update', $proof->order), ['payment_status' => 'paid', 'note' => 'Own payment'])->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_manual_override_requires_reason_and_records_actor_and_reversal(): void
    {
        $order = Order::factory()->manual()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.orders.manual-payment.update', $order);
        $this->actingAs($admin)->patchJson($url, ['payment_status' => 'paid'])->assertUnprocessable()->assertJsonValidationErrors('note');

        $this->patchJson($url, ['payment_status' => 'paid', 'note' => 'Transfer diterima di luar website.'])->assertOk();
        $this->patchJson($url, ['payment_status' => 'unpaid', 'note' => 'Koreksi salah pesanan.'])->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'unpaid', 'paid_at' => null]);
        $this->assertDatabaseHas('payment_status_logs', ['source' => 'admin_manual', 'changed_by_user_id' => $admin->id, 'from_status' => 'paid', 'to_status' => 'unpaid']);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_midtrans_override_and_processing_order_reversal_are_rejected(): void
    {
        $order = Order::factory()->midtrans()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson(route('admin.orders.manual-payment.update', $order), ['payment_status' => 'paid', 'note' => 'Override attempt'])->assertUnprocessable();
        $manual = Order::factory()->manual()->paid()->create(['status' => 'processing']);

        $this->patchJson(route('admin.orders.manual-payment.update', $manual), ['payment_status' => 'unpaid', 'note' => 'Reverse attempt'])->assertUnprocessable();

        $this->assertDatabaseCount('payment_status_logs', 0);
    }

    #[TestWith(['processing', 'pending'])]
    #[TestWith(['completed', 'completed'])]
    #[TestWith(['pending', 'processing'])]
    public function test_unpaid_orders_cannot_be_fulfilled(string $status, string $shipping): void
    {
        $order = Order::factory()->manual()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson(route('admin.orders.status', $order), ['status' => $status, 'shipping_status' => $shipping])->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'shipping_status' => 'pending']);
    }

    public function test_paid_order_cannot_be_cancelled_before_refund(): void
    {
        $order = Order::factory()->manual()->paid()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_manual_cancellation_restocks_only_once(): void
    {
        $order = Order::factory()->manual()->create();
        $product = Product::factory()->create(['stock' => 3]);
        OrderItem::factory()->for($order)->for($product)->create(['qty' => 2]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertOk();

        $this->patchJson(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_webhook_rejects_invalid_signature_and_amount(): void
    {
        $order = Order::factory()->midtrans()->create();
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('available')->andReturnTrue();
            $mock->shouldNotReceive('status');
        });
        $data = $this->notification($order);

        $this->postJson(route('payments.midtrans.notification'), [...$data, 'signature_key' => str_repeat('0', 128)])->assertForbidden();
        $this->postJson(route('payments.midtrans.notification'), $this->notification($order, ['gross_amount' => '1.00']))->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_duplicate_out_of_order_and_failed_attempt_notifications_preserve_paid_order(): void
    {
        $order = Order::factory()->midtrans()->create();
        $paid = $this->notification($order);
        $pending = $this->notification($order, ['transaction_status' => 'pending']);
        $failed = $this->notification($order, ['transaction_id' => 'old-attempt', 'transaction_status' => 'deny']);
        $this->mock(MidtransService::class, function (MockInterface $mock) use ($paid, $pending, $failed): void {
            $mock->shouldReceive('available')->andReturnTrue();
            $mock->shouldReceive('status')->with('transaction-1')->times(3)->andReturn($paid, $paid, $pending);
            $mock->shouldReceive('status')->with('old-attempt')->once()->andReturn($failed);
        });
        $url = route('payments.midtrans.notification');

        $this->postJson($url, $paid)->assertOk();
        $this->postJson($url, $paid)->assertOk();
        $this->postJson($url, $pending)->assertOk();
        $this->postJson($url, $failed)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'status' => 'pending', 'transaction_id' => 'transaction-1']);
        $this->assertDatabaseHas('payments', ['transaction_id' => 'transaction-1', 'transaction_status' => 'settlement']);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('payment_status_logs', 1);
        $this->assertArrayNotHasKey('signature_key', Payment::query()->firstOrFail()->raw_notification);
    }

    public function test_webhook_uses_api_status_instead_of_tampered_status(): void
    {
        $order = Order::factory()->midtrans()->create();
        $this->gatewayStatus($this->notification($order, ['transaction_status' => 'pending']), 'transaction-1');

        $this->postJson(route('payments.midtrans.notification'), $this->notification($order))->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'unpaid']);
    }

    #[TestWith(['capture', 'accept', 'paid'])]
    #[TestWith(['capture', 'challenge', 'unpaid'])]
    #[TestWith(['capture', 'deny', 'failed'])]
    #[TestWith(['pending', 'accept', 'unpaid'])]
    #[TestWith(['deny', 'deny', 'failed'])]
    #[TestWith(['expire', 'accept', 'failed'])]
    public function test_verified_status_maps_payment_and_fraud_decisions(string $status, string $fraud, string $expected): void
    {
        $order = Order::factory()->midtrans()->create();
        $data = $this->notification($order, ['transaction_status' => $status, 'fraud_status' => $fraud]);
        $this->gatewayStatus($data, 'transaction-1');

        $this->postJson(route('payments.midtrans.notification'), $data)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => $expected, 'status' => 'pending']);
    }

    public function test_unavailable_verification_returns_503_without_changing_payment(): void
    {
        $order = Order::factory()->midtrans()->create();
        $this->gatewayStatus(null, 'transaction-1');

        $this->postJson(route('payments.midtrans.notification'), $this->notification($order))->assertServiceUnavailable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_expired_sync_restocks_once_and_manual_overdue_order_stays_pending(): void
    {
        $order = Order::factory()->midtrans()->create(['payment_due_at' => now()->subMinute()]);
        $manual = Order::factory()->manual()->create(['payment_due_at' => now()->subDay()]);
        $product = Product::factory()->create(['stock' => 3]);
        OrderItem::factory()->for($order)->for($product)->create(['qty' => 2]);
        $this->gatewayStatus($this->notification($order, ['transaction_status' => 'expire']), $order->midtrans_order_id);
        $this->actingAs($order->user)->postJson(route('orders.midtrans.sync', $order))->assertOk();

        $this->postJson(route('orders.midtrans.sync', $order))->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $manual->id, 'status' => 'pending', 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_sync_and_session_require_order_ownership(): void
    {
        $order = Order::factory()->midtrans()->create();

        $this->actingAs(User::factory()->create())->postJson(route('orders.midtrans.sync', $order))->assertForbidden();
        $this->postJson(route('orders.midtrans.session', $order))->assertForbidden();
    }

    public function test_refund_notifications_before_settlement_do_not_leave_an_order_unpaid(): void
    {
        $order = Order::factory()->midtrans()->create();
        $data = $this->notification($order, ['transaction_status' => 'refund']);
        $this->gatewayStatus($data, 'transaction-1');

        $this->postJson(route('payments.midtrans.notification'), $data)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'refunded']);
    }

    public function test_partial_refund_preserves_paid_status_and_records_gross_revenue_policy(): void
    {
        $order = Order::factory()->midtrans()->paid()->create(['transaction_id' => 'transaction-1']);
        $data = $this->notification($order, ['transaction_status' => 'partial_refund', 'refund_amount' => '2000.00']);
        $this->gatewayStatus($data, 'transaction-1');

        $this->postJson(route('payments.midtrans.notification'), $data)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $data)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseCount('payment_status_logs', 1);
        $this->assertSame('2000.00', Payment::query()->sole()->raw_notification['refund_amount']);
    }

    public function test_session_expiry_without_a_transaction_requires_confirmed_snap_cancellation(): void
    {
        $order = Order::factory()->midtrans()->create(['payment_due_at' => now()->subMinute(), 'snap_token' => 'expired-token']);
        $this->mock(MidtransService::class, function (MockInterface $mock) use ($order): void {
            $mock->shouldReceive('status')->with($order->midtrans_order_id)->twice()->andReturnNull();
            $mock->shouldReceive('cancelSession')->with('expired-token')->once()->andReturnTrue();
        });

        $this->actingAs($order->user)->postJson(route('orders.midtrans.sync', $order))->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'failed']);
    }

    public function test_unconfirmed_snap_cancellation_never_releases_stock(): void
    {
        $order = Order::factory()->midtrans()->create(['payment_due_at' => now()->subMinute(), 'snap_token' => 'busy-token']);
        $this->mock(MidtransService::class, function (MockInterface $mock) use ($order): void {
            $mock->shouldReceive('status')->with($order->midtrans_order_id)->once()->andReturnNull();
            $mock->shouldReceive('cancelSession')->with('busy-token')->once()->andReturnFalse();
        });

        $this->actingAs($order->user)->postJson(route('orders.midtrans.sync', $order))->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'payment_status' => 'unpaid']);
    }

    public function test_scheduler_reconciles_midtrans_and_leaves_manual_orders_alone(): void
    {
        $order = Order::factory()->midtrans()->create();
        $manual = Order::factory()->manual()->create(['payment_due_at' => now()->subDay()]);
        $this->gatewayStatus($this->notification($order), $order->midtrans_order_id);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $manual->id, 'payment_status' => 'unpaid', 'status' => 'pending']);
    }

    public function test_paid_order_can_proceed_to_processing_without_payment_changes(): void
    {
        $order = Order::factory()->manual()->paid()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson(route('admin.orders.status', $order), ['status' => 'processing', 'shipping_status' => 'processing', 'payment_status' => 'unpaid'])->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing', 'payment_status' => 'paid']);
    }

    public function test_reports_count_only_paid_revenue_and_products(): void
    {
        $paid = Order::factory()->manual()->paid()->create(['total' => 20000]);
        $unpaid = Order::factory()->manual()->create(['total' => 90000]);
        Order::factory()->create(['payment_status' => 'refunded', 'total' => 50000]);
        OrderItem::factory()->for($paid)->create(['product_name_snapshot' => 'Paid cake', 'qty' => 2, 'subtotal' => 20000]);
        OrderItem::factory()->for($unpaid)->create(['product_name_snapshot' => 'Unpaid cake', 'qty' => 9, 'subtotal' => 90000]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson(route('admin.reports.orders', ['period' => 'day']))->assertOk()->assertJsonPath('data.total_revenue', 20000)->assertJsonPath('data.average_order_value', 20000)->assertJsonPath('data.top_products.0.product', 'Paid cake')->assertJsonCount(1, 'data.top_products');
        $csv = $this->get(route('admin.reports.orders.download', ['period' => 'day']));
        $csv->assertOk();
        $this->assertStringContainsString('Payment Channel', $csv->streamedContent());
    }

    public function test_pages_show_client_key_and_hide_server_key_and_midtrans_override(): void
    {
        $order = Order::factory()->midtrans()->create(['snap_token' => 'snap-token']);

        $this->actingAs($order->user)->get(route('orders.show', $order))->assertOk()->assertSee('https://app.sandbox.midtrans.com/snap/snap.js', false)->assertSee('test-client-key')->assertDontSee('test-server-key');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.orders.show', $order))->assertOk()->assertSeeText('Sinkronkan Midtrans')->assertDontSee('name="payment_status"', false);
    }

    public function test_rejection_text_is_escaped_on_order_pages(): void
    {
        $proof = PaymentProof::factory()->rejected()->create(['rejection_reason' => '<script>alert("xss")</script>']);

        $this->actingAs($proof->order->user)->get(route('orders.show', $proof->order))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_payment_status_audit_records_cannot_be_edited_or_deleted(): void
    {
        $log = PaymentStatusLog::factory()->create();

        $this->assertFalse($log->update(['to_status' => 'unpaid']));
        $this->assertFalse($log->delete());

        $this->assertDatabaseHas('payment_status_logs', ['id' => $log->id, 'to_status' => 'paid']);
    }

    /** @return array{User, Product} */
    private function cart(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'price' => 10000]);
        CartItem::factory()->create(['cart_id' => $user->cart()->create()->id, 'product_id' => $product->id, 'qty' => 2]);

        return [$user, $product];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function notification(Order $order, array $overrides = []): array
    {
        $data = [...['order_id' => $order->midtrans_order_id, 'gross_amount' => $order->total, 'transaction_id' => 'transaction-1', 'transaction_status' => 'settlement', 'status_code' => '200', 'payment_type' => 'bank_transfer', 'merchant_id' => 'test-merchant', 'currency' => 'IDR'], ...$overrides];
        $data['signature_key'] = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].'test-server-key');

        return $data;
    }

    /** @param array<string, mixed>|null $data */
    private function gatewayStatus(?array $data, string $id): void
    {
        $this->mock(MidtransService::class, function (MockInterface $mock) use ($data, $id): void {
            $mock->shouldReceive('available')->andReturnTrue();
            $mock->shouldReceive('status')->with($id)->andReturn($data);
        });
    }
}

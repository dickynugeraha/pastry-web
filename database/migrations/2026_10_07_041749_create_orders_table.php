<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Kode order untuk ditampilkan ke user
            $table->string('order_code')->unique();

            // Kolom untuk Midtrans
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('fraud_status')->nullable();
            $table->string('pdf_url')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Total & status
            $table->decimal('total', 12, 2);
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->enum('payment_status', [
                'unpaid',
                'paid',
                'failed',
                'refunded',
            ])->default('unpaid');

            // Pengiriman / pengambilan dari toko
            $table->enum('delivery_method', ['delivery', 'pickup'])->default('delivery');
            $table->enum('shipping_status', [
                'pending',
                'processing',
                'delivering',
                'ready_for_pickup',
                'completed',
                'cancelled',
            ])->default('pending');

            // Data penerima (untuk delivery)
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('shipping_address')->nullable();

            // Kode pickup (untuk pickup)
            $table->string('pickup_code')->nullable()->unique();

            // Catatan pembeli
            $table->text('notes')->nullable();

            // Timestamp penting
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

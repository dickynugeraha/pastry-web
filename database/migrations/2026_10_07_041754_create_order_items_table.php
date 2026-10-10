<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            // nullable karena produk bisa saja dihapus setelah dipesan
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('qty')->default(1);
            $table->decimal('price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            // Snapshot data untuk produk yang dipesan, karena produk bisa saja dihapus atau diubah setelah dipesan
            $table->string('product_name_snapshot');
            $table->string('product_image_snapshot')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

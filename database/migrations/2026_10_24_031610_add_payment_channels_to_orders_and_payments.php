<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_channel')->nullable()->index();
            $table->string('manual_payment_method')->nullable();
            $table->json('manual_payment_details')->nullable();
            $table->timestamp('payment_due_at')->nullable();
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('provider')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('note')->nullable();
        });
        DB::table('orders')->whereNotNull('midtrans_order_id')->update(['payment_channel' => 'midtrans']);
        DB::table('payments')->whereNotNull('midtrans_order_id')->update(['provider' => 'midtrans']);
        if (! DB::table('payments')->whereNotNull('transaction_id')->select('transaction_id')->groupBy('transaction_id')->havingRaw('COUNT(*) > 1')->exists()) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->unique('transaction_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasIndex('payments', 'payments_transaction_id_unique')) {
                $table->dropUnique('payments_transaction_id_unique');
            }
            $table->dropConstrainedForeignId('confirmed_by_user_id');
            $table->dropColumn(['provider', 'confirmed_at', 'note']);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['payment_channel']);
            $table->dropColumn(['payment_channel', 'manual_payment_method', 'manual_payment_details', 'payment_due_at']);
        });
    }
};

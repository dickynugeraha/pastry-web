<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('payments:reconcile {--limit=100 : Maximum orders per run}')]
#[Description('Reconcile pending Midtrans payments and verified expired sessions')]
class ReconcileMidtransPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PaymentService $payments, MidtransService $midtrans): int
    {
        if (! $midtrans->available()) {
            return self::SUCCESS;
        }
        $failures = 0;
        $orders = Order::query()->where('payment_channel', 'midtrans')
            ->whereIn('payment_status', ['unpaid', 'failed'])->where('status', 'pending')
            ->orderBy('updated_at')->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->get();
        foreach ($orders as $order) {
            try {
                $payments->sync($order);
            } catch (Throwable) {
                $failures++;
                Log::warning('Midtrans reconciliation failed.', ['order_id' => $order->id]);
            } finally {
                $order->touch();
            }
        }
        $this->info('Reconciled '.$orders->count().' order(s); failures: '.$failures.'.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

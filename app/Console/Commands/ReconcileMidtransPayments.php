<?php

namespace App\Console\Commands;

use App\Enums\PaymentChannel;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('payments:reconcile {--limit=100 : Maximum orders per run}')]
#[Description('Reconcile pending payments and cancel verified expired orders')]
class ReconcileMidtransPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PaymentService $payments, MidtransService $midtrans): int
    {
        $midtransAvailable = $midtrans->available();
        $failures = 0;
        $orders = Order::query()
            ->whereIn('payment_status', ['unpaid', 'failed'])
            ->where('status', 'pending')
            ->where(function (Builder $query) use ($midtransAvailable): void {
                $query->where(function (Builder $query): void {
                    $query->where('payment_channel', PaymentChannel::MANUAL)
                        ->whereNotNull('payment_due_at')
                        ->where('payment_due_at', '<=', now());
                });

                if ($midtransAvailable) {
                    $query->orWhere('payment_channel', PaymentChannel::MIDTRANS);
                }
            })
            ->orderBy('updated_at')->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->get();

        foreach ($orders as $order) {
            try {
                if ($order->payment_channel === PaymentChannel::MANUAL) {
                    $payments->expireManualOrder($order);
                } else {
                    $payments->sync($order);
                }
            } catch (Throwable) {
                $failures++;
                Log::warning('Payment reconciliation failed.', ['order_id' => $order->id]);
            } finally {
                $order->touch();
            }
        }

        $this->info('Reconciled '.$orders->count().' order(s); failures: '.$failures.'.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

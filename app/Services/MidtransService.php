<?php

namespace App\Services;

use App\Exceptions\MidtransUnavailableException;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class MidtransService
{
    public function available(): bool
    {
        return config('payments.midtrans.enabled')
            && filled(config('payments.midtrans.server_key'))
            && filled(config('payments.midtrans.client_key'));
    }

    public function createSession(Order $order): string
    {
        $this->configure();
        try {
            $response = Snap::createTransaction([
                'transaction_details' => ['order_id' => $order->midtrans_order_id, 'gross_amount' => (float) $order->total],
                'customer_details' => ['first_name' => $order->recipient_name ?: $order->user->name, 'email' => $order->user->email, 'phone' => $order->recipient_phone],
                'callbacks' => ['finish' => route('orders.show', $order)],
                'expiry' => ['start_time' => $order->created_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'), 'unit' => 'minutes', 'duration' => config('payments.midtrans.expiry_minutes')],
            ]);
        } catch (Throwable) {
            throw new MidtransUnavailableException('Midtrans session could not be created. Please retry.');
        }
        if (! is_string($response->token ?? null) || $response->token === '') {
            throw new MidtransUnavailableException('Midtrans returned an invalid session.');
        }

        return $response->token;
    }

    /** @return array<string, mixed>|null */
    public function status(string $transactionId): ?array
    {
        $this->configure();
        try {
            return (array) Transaction::status(rawurlencode($transactionId));
        } catch (Throwable $exception) {
            if ((int) $exception->getCode() === 404) {
                return null;
            }
            throw new MidtransUnavailableException('Midtrans status is temporarily unavailable.');
        }
    }

    private function configure(): void
    {
        if (! $this->available()) {
            throw ValidationException::withMessages(['payment_channel' => 'Midtrans sedang tidak tersedia. Pilih pembayaran manual.']);
        }
        Config::$serverKey = config('payments.midtrans.server_key');
        Config::$clientKey = config('payments.midtrans.client_key');
        Config::$isProduction = (bool) config('payments.midtrans.is_production');
        Config::$is3ds = true;
        Config::$isSanitized = false;
        Config::$curlOptions = [CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => []];
    }

    public function cancelSession(string $token): bool
    {
        $this->configure();
        try {
            $response = Http::withBasicAuth(config('payments.midtrans.server_key'), '')
                ->acceptJson()->connectTimeout(5)->timeout(20)
                ->post(Config::getSnapBaseUrl().'/transactions/'.rawurlencode($token).'/cancel');

            return $response->successful() && filled($response->json('canceled_at'));
        } catch (Throwable) {
            throw new MidtransUnavailableException('Midtrans session cancellation is temporarily unavailable.');
        }
    }
}

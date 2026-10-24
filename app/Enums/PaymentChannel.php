<?php

namespace App\Enums;

enum PaymentChannel: string
{
    case MIDTRANS = 'midtrans';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::MIDTRANS => 'Midtrans',
            self::MANUAL => 'Pembayaran manual',
        };
    }
}

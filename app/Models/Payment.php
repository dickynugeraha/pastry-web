<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'provider',
        'confirmed_by_user_id',
        'confirmed_at',
        'note',
        'order_id',
        'midtrans_order_id',
        'transaction_id',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'gross_amount',
        'raw_notification',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'gross_amount' => 'decimal:2',
        'raw_notification' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}

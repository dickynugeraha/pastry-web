<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\ManualPaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\ShippingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'payment_channel',
        'manual_payment_method',
        'manual_payment_details',
        'payment_due_at',
        'order_code',
        'midtrans_order_id',
        'snap_token',
        'transaction_id',
        'payment_type',
        'fraud_status',
        'pdf_url',
        'paid_at',
        'total',
        'status',
        'payment_status',
        'delivery_method',
        'shipping_status',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'pickup_code',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'payment_channel' => PaymentChannel::class,
        'manual_payment_method' => ManualPaymentMethod::class,
        'manual_payment_details' => 'array',
        'payment_due_at' => 'datetime',
        'delivery_method' => DeliveryMethod::class,
        'shipping_status' => ShippingStatus::class,
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->latest('id');
    }

    public function paymentStatusLogs(): HasMany
    {
        return $this->hasMany(PaymentStatusLog::class)->latest('id');
    }

    public function acceptsPaymentProof(): bool
    {
        return $this->payment_channel === PaymentChannel::MANUAL
            && $this->payment_status === 'unpaid'
            && ! in_array($this->status, ['cancelled', 'completed'], true)
            && ! $this->shipping_status->isFinal();
    }

    public function canTransitionTo(ShippingStatus $newStatus): bool
    {
        if ($this->shipping_status->isFinal()) {
            return false;
        }

        $allowed = $this->delivery_method === DeliveryMethod::DELIVERY
            ? ShippingStatus::forDelivery()
            : ShippingStatus::forPickup();

        return in_array($newStatus, $allowed);
    }
}

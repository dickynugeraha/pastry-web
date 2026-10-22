<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
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
        return $this->hasOne(Payment::class);
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

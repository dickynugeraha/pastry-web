<?php

namespace App\Models;

use Database\Factories\PaymentStatusLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStatusLog extends Model
{
    /** @use HasFactory<PaymentStatusLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'from_status', 'to_status', 'source', 'changed_by_user_id', 'external_transaction_id', 'note', 'context'];

    protected $casts = ['context' => 'array'];

    protected static function booted(): void
    {
        static::updating(fn (): bool => false);
        static::deleting(fn (): bool => false);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

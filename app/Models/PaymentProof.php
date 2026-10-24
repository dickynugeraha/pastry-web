<?php

namespace App\Models;

use Database\Factories\PaymentProofFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    /** @use HasFactory<PaymentProofFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'uploaded_by_user_id', 'manual_payment_method', 'disk', 'file_path', 'original_name', 'mime_type', 'size_bytes', 'review_status', 'reviewed_by_user_id', 'reviewed_at', 'rejection_reason'];

    protected $hidden = ['file_path', 'disk'];

    protected $casts = ['reviewed_at' => 'datetime', 'size_bytes' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}

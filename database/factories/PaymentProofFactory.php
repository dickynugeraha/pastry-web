<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProof>
 */
class PaymentProofFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->manual(),
            'uploaded_by_user_id' => fn (array $attributes): int => Order::findOrFail($attributes['order_id'])->user_id,
            'manual_payment_method' => 'bca', 'disk' => 'local', 'file_path' => 'payment-proofs/'.fake()->uuid().'.png',
            'original_name' => 'bukti.png', 'mime_type' => 'image/png', 'size_bytes' => 100, 'review_status' => 'pending',
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => ['review_status' => 'accepted', 'reviewed_by_user_id' => User::factory()->state(['role' => 'admin']), 'reviewed_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['review_status' => 'pending', 'reviewed_by_user_id' => null, 'reviewed_at' => null, 'rejection_reason' => null]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['review_status' => 'rejected', 'reviewed_by_user_id' => User::factory()->state(['role' => 'admin']), 'reviewed_at' => now(), 'rejection_reason' => 'Bukti kurang jelas.']);
    }
}

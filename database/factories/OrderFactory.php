<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_code' => 'BK-'.fake()->unique()->numerify('########'),
            'total' => 10000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_method' => 'delivery',
            'shipping_status' => 'pending',
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
        ];
    }

    public function manual(string $method = 'bca'): static
    {
        return $this->state(fn (): array => ['payment_channel' => 'manual', 'manual_payment_method' => $method, 'manual_payment_details' => config('payments.manual.'.$method), 'payment_due_at' => now()->addHour()]);
    }

    public function midtrans(): static
    {
        return $this->state(fn (): array => ['payment_channel' => 'midtrans', 'midtrans_order_id' => 'BK-'.fake()->uuid(), 'payment_due_at' => now()->addHour()]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['payment_status' => 'paid', 'paid_at' => now()]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (): array => ['payment_status' => 'unpaid', 'paid_at' => null]);
    }
}

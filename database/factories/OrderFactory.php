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
}

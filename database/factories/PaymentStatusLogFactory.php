<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentStatusLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentStatusLog>
 */
class PaymentStatusLogFactory extends Factory
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
            'from_status' => 'unpaid', 'to_status' => 'paid', 'source' => 'admin_manual', 'note' => 'Pembayaran dikonfirmasi.',
        ];
    }
}

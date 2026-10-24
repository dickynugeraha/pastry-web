<?php

namespace Database\Seeders;

use App\Models\PaymentStatusLog;
use Illuminate\Database\Seeder;

class PaymentStatusLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            PaymentStatusLog::factory()->create();
        }
    }
}

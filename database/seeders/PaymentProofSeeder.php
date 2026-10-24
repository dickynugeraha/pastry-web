<?php

namespace Database\Seeders;

use App\Models\PaymentProof;
use Illuminate\Database\Seeder;

class PaymentProofSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            PaymentProof::factory()->create();
        }
    }
}

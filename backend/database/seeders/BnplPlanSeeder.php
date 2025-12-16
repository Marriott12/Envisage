<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BnplPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = [
            [
                'name' => 'Pay in 3 - Bi-weekly',
                'installments' => 3,
                'interval_days' => 14,
                'interest_rate' => 0.00,
                'minimum_amount' => 50.00,
                'maximum_amount' => 1000.00,
                'active' => true,
                'terms' => 'Split your purchase into 3 equal payments, every 2 weeks. No interest, no fees.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pay in 4 - Monthly',
                'installments' => 4,
                'interval_days' => 30,
                'interest_rate' => 0.00,
                'minimum_amount' => 100.00,
                'maximum_amount' => 2000.00,
                'active' => true,
                'terms' => 'Split your purchase into 4 equal monthly payments. 0% APR, no hidden fees.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pay in 6 - Monthly',
                'installments' => 6,
                'interval_days' => 30,
                'interest_rate' => 2.99,
                'minimum_amount' => 200.00,
                'maximum_amount' => 3000.00,
                'active' => true,
                'terms' => 'Split your purchase into 6 monthly payments. 2.99% APR applied.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pay in 12 - Monthly',
                'installments' => 12,
                'interval_days' => 30,
                'interest_rate' => 5.99,
                'minimum_amount' => 500.00,
                'maximum_amount' => 5000.00,
                'active' => true,
                'terms' => 'Split your purchase into 12 monthly payments. 5.99% APR for larger purchases.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Express 2 Weeks',
                'installments' => 2,
                'interval_days' => 7,
                'interest_rate' => 0.00,
                'minimum_amount' => 25.00,
                'maximum_amount' => 500.00,
                'active' => true,
                'terms' => 'Quick payment plan with 2 weekly payments. Perfect for small purchases.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('bnpl_plans')->insert($plans);

        $this->command->info('BNPL plans seeded successfully!');
    }
}

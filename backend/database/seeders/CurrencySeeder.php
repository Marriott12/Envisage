<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'rate' => 1.000000, 'is_base' => true, 'format' => '{symbol}{amount}'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'rate' => 0.920000, 'format' => '{symbol}{amount}'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'rate' => 0.790000, 'format' => '{symbol}{amount}'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'rate' => 148.500000, 'decimal_places' => 0, 'format' => '{symbol}{amount}'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'rate' => 1.520000, 'format' => '{symbol}{amount}'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'rate' => 1.350000, 'format' => '{symbol}{amount}'],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'rate' => 0.880000, 'format' => '{symbol} {amount}'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'rate' => 7.240000, 'format' => '{symbol}{amount}'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'rate' => 83.200000, 'format' => '{symbol}{amount}'],
            ['code' => 'ZMW', 'name' => 'Zambian Kwacha', 'symbol' => 'ZK', 'rate' => 24.500000, 'format' => '{symbol}{amount}'],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                array_merge([
                    'is_active' => true,
                    'is_base' => false,
                    'decimal_places' => 2,
                ], $currency)
            );
        }
    }
}

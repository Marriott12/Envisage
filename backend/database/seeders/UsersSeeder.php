<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@envisage.com',
            'password' => Hash::make('Admin@123'),
            'role' => 'admin',
            'phone' => '+260 971 234567',
            'address' => '123 Admin Street',
            'city' => 'Lusaka',
            'country' => 'Zambia',
            'email_verified_at' => now(),
        ]);

        // Create Test Seller
        User::create([
            'name' => 'John Seller',
            'email' => 'seller@envisage.com',
            'password' => Hash::make('Seller@123'),
            'role' => 'seller',
            'phone' => '+260 977 111222',
            'address' => '456 Market Road',
            'city' => 'Lusaka',
            'country' => 'Zambia',
            'email_verified_at' => now(),
        ]);

        // Create Test Buyer
        User::create([
            'name' => 'Jane Buyer',
            'email' => 'buyer@envisage.com',
            'password' => Hash::make('Buyer@123'),
            'role' => 'user',
            'phone' => '+260 966 333444',
            'address' => '789 Customer Avenue',
            'city' => 'Ndola',
            'country' => 'Zambia',
            'email_verified_at' => now(),
        ]);

        // Create additional test users
        User::factory(10)->create();

        $this->command->info('Users seeded successfully!');
        $this->command->info('Admin: admin@envisage.com / Admin@123');
        $this->command->info('Seller: seller@envisage.com / Seller@123');
        $this->command->info('Buyer: buyer@envisage.com / Buyer@123');
    }
}

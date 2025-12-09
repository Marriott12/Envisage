<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if admin user already exists
        $adminExists = User::where('email', 'admin@envisage.com')->first();

        if (!$adminExists) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@envisage.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@envisage.com');
            $this->command->info('Password: admin123');
        } else {
            // Update role to admin if user exists
            $adminExists->update(['role' => 'admin']);
            $this->command->warn('Admin user already exists. Role updated to admin.');
        }

        // Optional: Create test buyer and seller
        $buyerExists = User::where('email', 'buyer@envisage.com')->first();
        if (!$buyerExists) {
            User::create([
                'name' => 'Test Buyer',
                'email' => 'buyer@envisage.com',
                'password' => Hash::make('buyer123'),
                'role' => 'buyer',
                'email_verified_at' => now(),
            ]);
            $this->command->info('Test buyer created: buyer@envisage.com / buyer123');
        }

        $sellerExists = User::where('email', 'seller@envisage.com')->first();
        if (!$sellerExists) {
            User::create([
                'name' => 'Test Seller',
                'email' => 'seller@envisage.com',
                'password' => Hash::make('seller123'),
                'role' => 'seller',
                'email_verified_at' => now(),
            ]);
            $this->command->info('Test seller created: seller@envisage.com / seller123');
        }
    }
}

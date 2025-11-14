<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@envisagezm.com',
            'password' => bcrypt('Admin@2025'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create seller user
        \App\Models\User::create([
            'name' => 'Tech Store',
            'email' => 'techstore@envisagezm.com',
            'password' => bcrypt('Seller@2025'),
            'role' => 'seller',
            'email_verified_at' => now(),
        ]);

        // Create customer user
        \App\Models\User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Buyer@2025'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Seed categories
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }

        // Seed sample products
        $electronics = \App\Models\Category::where('slug', 'electronics')->first();
        
        \App\Models\Product::create([
            'title' => 'Wireless Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation',
            'price' => 99.99,
            'category_id' => $electronics->id,
            'seller_id' => 2, // seller
            'stock' => 50,
            'status' => 'active',
        ]);

        \App\Models\Product::create([
            'title' => 'Smart Watch',
            'description' => 'Feature-rich smart watch with fitness tracking',
            'price' => 199.99,
            'category_id' => $electronics->id,
            'seller_id' => 2, // seller
            'stock' => 30,
            'status' => 'active',
        ]);
    }
}

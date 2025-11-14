<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompleteMarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "🌱 Seeding Complete Marketplace Data...\n\n";

        // Create Admin User
        echo "👤 Creating admin user...\n";
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@envisagezm.com',
            'password' => Hash::make('Admin@2025'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
        echo "✓ Admin created: {$admin->email} / Admin@2025\n\n";

        // Create Sellers
        echo "👥 Creating seller accounts...\n";
        $seller1 = User::create([
            'name' => 'TechStore Zambia',
            'email' => 'techstore@envisagezm.com',
            'password' => Hash::make('Seller@2025'),
            'email_verified_at' => now(),
            'role' => 'seller',
        ]);

        $seller2 = User::create([
            'name' => 'Electronics Hub',
            'email' => 'electronics@envisagezm.com',
            'password' => Hash::make('Seller@2025'),
            'email_verified_at' => now(),
            'role' => 'seller',
        ]);

        $seller3 = User::create([
            'name' => 'Fashion Corner',
            'email' => 'fashion@envisagezm.com',
            'password' => Hash::make('Seller@2025'),
            'email_verified_at' => now(),
            'role' => 'seller',
        ]);
        echo "✓ 3 sellers created\n\n";

        // Create Buyers
        echo "🛍️ Creating buyer accounts...\n";
        $buyer1 = User::create([
            'name' => 'John Mwansa',
            'email' => 'john@example.com',
            'password' => Hash::make('Buyer@2025'),
            'email_verified_at' => now(),
            'role' => 'buyer',
        ]);

        $buyer2 = User::create([
            'name' => 'Sarah Banda',
            'email' => 'sarah@example.com',
            'password' => Hash::make('Buyer@2025'),
            'email_verified_at' => now(),
            'role' => 'buyer',
        ]);
        echo "✓ 2 buyers created\n\n";

        // Create Categories
        echo "📁 Creating categories...\n";
        
        // Main Categories
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and gadgets',
        ]);

        $fashion = Category::create([
            'name' => 'Fashion & Clothing',
            'slug' => 'fashion-clothing',
            'description' => 'Clothing, shoes, and accessories',
        ]);

        $home = Category::create([
            'name' => 'Home & Garden',
            'slug' => 'home-garden',
            'description' => 'Furniture, décor, and garden items',
        ]);

        $sports = Category::create([
            'name' => 'Sports & Outdoors',
            'slug' => 'sports-outdoors',
            'description' => 'Sports equipment and outdoor gear',
        ]);

        // Electronics Subcategories
        $computers = Category::create([
            'name' => 'Computers & Laptops',
            'slug' => 'computers-laptops',
            'description' => 'Desktop computers and laptops',
            'parent_id' => $electronics->id,
        ]);

        $phones = Category::create([
            'name' => 'Mobile Phones',
            'slug' => 'mobile-phones',
            'description' => 'Smartphones and feature phones',
            'parent_id' => $electronics->id,
        ]);

        $accessories = Category::create([
            'name' => 'Computer Accessories',
            'slug' => 'computer-accessories',
            'description' => 'Mice, keyboards, headphones, etc.',
            'parent_id' => $electronics->id,
        ]);

        // Fashion Subcategories
        $mensWear = Category::create([
            'name' => "Men's Clothing",
            'slug' => 'mens-clothing',
            'description' => 'Clothing for men',
            'parent_id' => $fashion->id,
        ]);

        $womensWear = Category::create([
            'name' => "Women's Clothing",
            'slug' => 'womens-clothing',
            'description' => 'Clothing for women',
            'parent_id' => $fashion->id,
        ]);

        echo "✓ 9 categories created\n\n";

        // Create Products
        echo "🛒 Creating products...\n";

        // Electronics Products
        $products = [
            // Laptops
            [
                'seller_id' => $seller1->id,
                'category_id' => $computers->id,
                'title' => 'Dell XPS 15 Laptop',
                'description' => 'High-performance laptop with Intel i7 processor, 16GB RAM, 512GB SSD. Perfect for professionals and content creators. Stunning 4K display with excellent color accuracy.',
                'price' => 1899.99,
                'stock' => 8,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Dell',
                'weight' => 2.0,
                'dimensions' => '35.7 x 23.5 x 1.8 cm',
                'featured' => true,
            ],
            [
                'seller_id' => $seller1->id,
                'category_id' => $computers->id,
                'title' => 'MacBook Air M2',
                'description' => 'Apple MacBook Air with M2 chip, 8GB unified memory, 256GB SSD. Ultra-portable and incredibly powerful. All-day battery life.',
                'price' => 1499.00,
                'stock' => 5,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Apple',
                'weight' => 1.24,
                'dimensions' => '30.41 x 21.5 x 1.61 cm',
                'featured' => true,
            ],
            [
                'seller_id' => $seller2->id,
                'category_id' => $computers->id,
                'title' => 'HP Pavilion Gaming Laptop',
                'description' => 'Gaming laptop with NVIDIA GTX 1650, Intel i5, 8GB RAM, 256GB SSD. Great for gaming and multimedia tasks.',
                'price' => 899.99,
                'stock' => 12,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'HP',
                'weight' => 2.3,
                'dimensions' => '36 x 25.6 x 2.3 cm',
                'featured' => false,
            ],

            // Mobile Phones
            [
                'seller_id' => $seller2->id,
                'category_id' => $phones->id,
                'title' => 'iPhone 14 Pro Max',
                'description' => 'Latest iPhone with A16 Bionic chip, 256GB storage, pro camera system. Dynamic Island, always-on display, and amazing battery life.',
                'price' => 1299.00,
                'stock' => 15,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Apple',
                'weight' => 0.24,
                'dimensions' => '16.07 x 7.81 x 0.78 cm',
                'featured' => true,
            ],
            [
                'seller_id' => $seller2->id,
                'category_id' => $phones->id,
                'title' => 'Samsung Galaxy S23 Ultra',
                'description' => 'Flagship Android phone with 200MP camera, S Pen, 12GB RAM, 512GB storage. Stunning 6.8" AMOLED display.',
                'price' => 1199.00,
                'stock' => 10,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Samsung',
                'weight' => 0.23,
                'dimensions' => '16.36 x 7.82 x 0.89 cm',
                'featured' => true,
            ],
            [
                'seller_id' => $seller1->id,
                'category_id' => $phones->id,
                'title' => 'Google Pixel 7 Pro',
                'description' => 'Pure Android experience with incredible camera, Google Tensor G2 chip, 128GB storage. Best-in-class photography.',
                'price' => 799.00,
                'stock' => 20,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Google',
                'weight' => 0.21,
                'dimensions' => '16.29 x 7.63 x 0.88 cm',
                'featured' => false,
            ],

            // Accessories
            [
                'seller_id' => $seller1->id,
                'category_id' => $accessories->id,
                'title' => 'Logitech MX Master 3S',
                'description' => 'Premium wireless mouse with precise scrolling, customizable buttons, and multi-device connectivity. Ergonomic design for all-day comfort.',
                'price' => 99.99,
                'stock' => 50,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Logitech',
                'weight' => 0.14,
                'dimensions' => '12.5 x 8.4 x 5.1 cm',
                'featured' => false,
            ],
            [
                'seller_id' => $seller2->id,
                'category_id' => $accessories->id,
                'title' => 'Mechanical Keyboard RGB',
                'description' => 'Gaming mechanical keyboard with customizable RGB lighting, Cherry MX switches, and premium build quality.',
                'price' => 149.99,
                'stock' => 30,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Corsair',
                'weight' => 1.2,
                'dimensions' => '44 x 16.6 x 4.2 cm',
                'featured' => false,
            ],
            [
                'seller_id' => $seller1->id,
                'category_id' => $accessories->id,
                'title' => 'Sony WH-1000XM5 Headphones',
                'description' => 'Industry-leading noise canceling wireless headphones. Premium sound quality, 30-hour battery life, and exceptional comfort.',
                'price' => 399.99,
                'stock' => 25,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Sony',
                'weight' => 0.25,
                'dimensions' => '20 x 18 x 8 cm',
                'featured' => true,
            ],
            [
                'seller_id' => $seller2->id,
                'category_id' => $accessories->id,
                'title' => 'USB-C Hub 7-in-1',
                'description' => '7-port USB-C hub with HDMI, USB 3.0, SD/TF card reader, and 100W power delivery. Perfect for MacBooks and laptops.',
                'price' => 49.99,
                'stock' => 100,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Anker',
                'weight' => 0.1,
                'dimensions' => '11 x 4.5 x 1.3 cm',
                'featured' => false,
            ],

            // Fashion Products
            [
                'seller_id' => $seller3->id,
                'category_id' => $mensWear->id,
                'title' => 'Classic Cotton T-Shirt',
                'description' => 'Premium quality cotton t-shirt. Available in multiple colors. Comfortable, breathable, and perfect for everyday wear.',
                'price' => 19.99,
                'stock' => 200,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Uniqlo',
                'weight' => 0.2,
                'featured' => false,
            ],
            [
                'seller_id' => $seller3->id,
                'category_id' => $mensWear->id,
                'title' => 'Slim Fit Jeans',
                'description' => 'Modern slim fit jeans with stretch denim. Durable and stylish. Perfect fit for any occasion.',
                'price' => 59.99,
                'stock' => 80,
                'status' => 'active',
                'condition' => 'new',
                'brand' => "Levi's",
                'weight' => 0.6,
                'featured' => false,
            ],
            [
                'seller_id' => $seller3->id,
                'category_id' => $womensWear->id,
                'title' => 'Summer Floral Dress',
                'description' => 'Beautiful floral pattern dress perfect for summer. Light, comfortable fabric. Available in S, M, L, XL sizes.',
                'price' => 45.00,
                'stock' => 60,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Zara',
                'weight' => 0.3,
                'featured' => false,
            ],
            [
                'seller_id' => $seller3->id,
                'category_id' => $womensWear->id,
                'title' => 'Leather Handbag',
                'description' => 'Genuine leather handbag with multiple compartments. Elegant design suitable for work and casual outings.',
                'price' => 129.99,
                'stock' => 35,
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Coach',
                'weight' => 0.8,
                'featured' => true,
            ],

            // Used/Refurbished Items
            [
                'seller_id' => $seller1->id,
                'category_id' => $computers->id,
                'title' => 'Refurbished ThinkPad X1 Carbon',
                'description' => 'Professional-grade laptop, refurbished to like-new condition. Intel i7, 16GB RAM, 512GB SSD. Tested and certified.',
                'price' => 799.00,
                'stock' => 5,
                'status' => 'active',
                'condition' => 'refurbished',
                'brand' => 'Lenovo',
                'weight' => 1.1,
                'dimensions' => '32.3 x 21.7 x 1.49 cm',
                'featured' => false,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        echo "✓ 15 products created\n\n";

        // Create Settings
        echo "⚙️ Creating site settings...\n";
        
        $settings = [
            ['key' => 'site_name', 'value' => 'Envisage Marketplace', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_description', 'value' => 'Buy and sell items securely with escrow protection', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_email', 'value' => 'support@envisagezm.com', 'group' => 'general', 'type' => 'email'],
            ['key' => 'site_phone', 'value' => '+260 XXX XXX XXX', 'group' => 'general', 'type' => 'text'],
            ['key' => 'commission_rate', 'value' => '5', 'group' => 'payments', 'type' => 'number'],
            ['key' => 'currency', 'value' => 'USD', 'group' => 'payments', 'type' => 'text'],
            ['key' => 'tax_rate', 'value' => '16', 'group' => 'payments', 'type' => 'number'],
            ['key' => 'enable_escrow', 'value' => 'true', 'group' => 'features', 'type' => 'boolean'],
            ['key' => 'enable_reviews', 'value' => 'true', 'group' => 'features', 'type' => 'boolean'],
            ['key' => 'enable_chat', 'value' => 'true', 'group' => 'features', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }

        echo "✓ Site settings configured\n\n";

        echo "✅ Marketplace seeding completed successfully!\n\n";
        echo "===========================================\n";
        echo "📧 CREDENTIALS:\n";
        echo "===========================================\n";
        echo "Admin:    admin@envisagezm.com / Admin@2025\n";
        echo "Seller 1: techstore@envisagezm.com / Seller@2025\n";
        echo "Seller 2: electronics@envisagezm.com / Seller@2025\n";
        echo "Seller 3: fashion@envisagezm.com / Seller@2025\n";
        echo "Buyer 1:  john@example.com / Buyer@2025\n";
        echo "Buyer 2:  sarah@example.com / Buyer@2025\n";
        echo "===========================================\n\n";
    }
}

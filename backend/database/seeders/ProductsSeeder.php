<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get or create a seller - use first user or create demo user
        $seller = User::first();
        if (!$seller) {
            $seller = User::create([
                'name' => 'Demo Seller',
                'email' => 'seller@envisage.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Get categories
        $electronics = Category::where('slug', 'electronics')->first();
        $fashion = Category::where('slug', 'fashion')->first();
        $home = Category::where('slug', 'home-garden')->first();
        $sports = Category::where('slug', 'sports-outdoors')->first();
        $books = Category::where('slug', 'books-media')->first();

        // Sample products
        $products = [
            // Electronics
            [
                'seller_id' => $seller->id,
                'category_id' => $electronics->id ?? 1,
                'title' => 'Samsung Galaxy A54 5G Smartphone',
                'description' => 'Latest 5G smartphone with 128GB storage, 6.4" Super AMOLED display, 50MP camera, and long-lasting battery.',
                'price' => 3299.00,
                'stock' => 15,
                'images' => json_encode(['https://via.placeholder.com/400x400/0066cc/ffffff?text=Galaxy+A54']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Samsung',
                'weight' => 0.2,
                'dimensions' => '158.2 x 76.7 x 8.2 mm',
                'featured' => true,
                'views' => 245,
                'sold' => 12,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $electronics->id ?? 1,
                'title' => 'HP Pavilion Laptop 15.6" Intel i5',
                'description' => 'Powerful laptop with Intel Core i5, 8GB RAM, 512GB SSD, perfect for work and entertainment.',
                'price' => 5499.00,
                'stock' => 8,
                'images' => json_encode(['https://via.placeholder.com/400x400/1a1a1a/ffffff?text=HP+Pavilion']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'HP',
                'weight' => 1.8,
                'dimensions' => '358 x 242 x 19 mm',
                'featured' => true,
                'views' => 189,
                'sold' => 5,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $electronics->id ?? 1,
                'title' => 'Sony WH-1000XM5 Wireless Headphones',
                'description' => 'Industry-leading noise cancellation, exceptional sound quality, 30-hour battery life.',
                'price' => 2799.00,
                'stock' => 20,
                'images' => json_encode(['https://via.placeholder.com/400x400/000000/ffffff?text=Sony+WH-1000XM5']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Sony',
                'weight' => 0.25,
                'featured' => false,
                'views' => 156,
                'sold' => 18,
            ],

            // Fashion
            [
                'seller_id' => $seller->id,
                'category_id' => $fashion->id ?? 2,
                'title' => 'Nike Air Max 270 Running Shoes - Men',
                'description' => 'Comfortable running shoes with Max Air cushioning, breathable mesh upper, available in multiple sizes.',
                'price' => 899.00,
                'stock' => 30,
                'images' => json_encode(['https://via.placeholder.com/400x400/ff6600/ffffff?text=Nike+Air+Max']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Nike',
                'weight' => 0.5,
                'featured' => true,
                'views' => 312,
                'sold' => 45,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $fashion->id ?? 2,
                'title' => 'Levi\'s 501 Original Fit Jeans',
                'description' => 'Classic straight fit jeans, durable denim, iconic 5-pocket styling. Size 32.',
                'price' => 450.00,
                'stock' => 25,
                'images' => json_encode(['https://via.placeholder.com/400x400/003366/ffffff?text=Levis+501']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Levi\'s',
                'weight' => 0.6,
                'featured' => false,
                'views' => 178,
                'sold' => 22,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $fashion->id ?? 2,
                'title' => 'Ray-Ban Aviator Classic Sunglasses',
                'description' => 'Iconic aviator sunglasses with UV protection, gold frame, green classic lenses.',
                'price' => 650.00,
                'stock' => 12,
                'images' => json_encode(['https://via.placeholder.com/400x400/ccaa00/000000?text=Ray-Ban']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Ray-Ban',
                'weight' => 0.1,
                'featured' => false,
                'views' => 98,
                'sold' => 8,
            ],

            // Home & Garden
            [
                'seller_id' => $seller->id,
                'category_id' => $home->id ?? 3,
                'title' => 'Dyson V11 Cordless Vacuum Cleaner',
                'description' => 'Powerful cordless vacuum with intelligent cleaning modes, up to 60 minutes runtime.',
                'price' => 4299.00,
                'stock' => 6,
                'images' => json_encode(['https://via.placeholder.com/400x400/6600cc/ffffff?text=Dyson+V11']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Dyson',
                'weight' => 2.9,
                'featured' => true,
                'views' => 134,
                'sold' => 3,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $home->id ?? 3,
                'title' => 'Philips Air Fryer XXL - 7.3L',
                'description' => 'Extra-large air fryer with Rapid Air technology, perfect for families. Cook healthier meals.',
                'price' => 1899.00,
                'stock' => 10,
                'images' => json_encode(['https://via.placeholder.com/400x400/333333/ffffff?text=Philips+Air+Fryer']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Philips',
                'weight' => 4.5,
                'featured' => false,
                'views' => 267,
                'sold' => 14,
            ],

            // Sports & Outdoors
            [
                'seller_id' => $seller->id,
                'category_id' => $sports->id ?? 4,
                'title' => 'Yoga Mat Premium 6mm Non-Slip',
                'description' => 'Eco-friendly yoga mat with excellent grip, comfortable cushioning, includes carrying strap.',
                'price' => 250.00,
                'stock' => 40,
                'images' => json_encode(['https://via.placeholder.com/400x400/009933/ffffff?text=Yoga+Mat']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'FitLife',
                'weight' => 1.2,
                'featured' => false,
                'views' => 89,
                'sold' => 31,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $sports->id ?? 4,
                'title' => 'Adjustable Dumbbell Set 5-25kg',
                'description' => 'Space-saving adjustable dumbbells, easy weight selection, perfect for home workouts.',
                'price' => 1599.00,
                'stock' => 15,
                'images' => json_encode(['https://via.placeholder.com/400x400/cc0000/ffffff?text=Dumbbells']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'PowerBlock',
                'weight' => 25.0,
                'featured' => true,
                'views' => 201,
                'sold' => 9,
            ],

            // Books & Media
            [
                'seller_id' => $seller->id,
                'category_id' => $books->id ?? 5,
                'title' => 'Atomic Habits by James Clear',
                'description' => 'Bestselling book on building good habits and breaking bad ones. Paperback edition.',
                'price' => 120.00,
                'stock' => 50,
                'images' => json_encode(['https://via.placeholder.com/400x400/003399/ffffff?text=Atomic+Habits']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Penguin Random House',
                'weight' => 0.3,
                'featured' => false,
                'views' => 412,
                'sold' => 67,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $books->id ?? 5,
                'title' => 'The 48 Laws of Power - Robert Greene',
                'description' => 'Classic book on strategy and power dynamics. Hardcover edition.',
                'price' => 180.00,
                'stock' => 35,
                'images' => json_encode(['https://via.placeholder.com/400x400/660000/ffffff?text=48+Laws']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Profile Books',
                'weight' => 0.5,
                'featured' => false,
                'views' => 278,
                'sold' => 43,
            ],

            // More varied products
            [
                'seller_id' => $seller->id,
                'category_id' => $electronics->id ?? 1,
                'title' => 'Apple AirPods Pro (2nd Gen)',
                'description' => 'Active noise cancellation, adaptive transparency, personalized spatial audio.',
                'price' => 1999.00,
                'stock' => 0,
                'images' => json_encode(['https://via.placeholder.com/400x400/ffffff/000000?text=AirPods+Pro']),
                'status' => 'out_of_stock',
                'condition' => 'new',
                'brand' => 'Apple',
                'weight' => 0.05,
                'featured' => false,
                'views' => 567,
                'sold' => 89,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $fashion->id ?? 2,
                'title' => 'Adidas Originals Trefoil Hoodie',
                'description' => 'Comfortable cotton hoodie with iconic trefoil logo. Size M.',
                'price' => 550.00,
                'stock' => 18,
                'images' => json_encode(['https://via.placeholder.com/400x400/000000/ffffff?text=Adidas+Hoodie']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Adidas',
                'weight' => 0.4,
                'featured' => false,
                'views' => 145,
                'sold' => 16,
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $home->id ?? 3,
                'title' => 'Instant Pot Duo 7-in-1 Electric Pressure Cooker',
                'description' => 'Multi-use programmable cooker: pressure cooker, slow cooker, rice cooker, and more.',
                'price' => 1299.00,
                'stock' => 12,
                'images' => json_encode(['https://via.placeholder.com/400x400/cc3300/ffffff?text=Instant+Pot']),
                'status' => 'active',
                'condition' => 'new',
                'brand' => 'Instant Pot',
                'weight' => 5.4,
                'featured' => true,
                'views' => 198,
                'sold' => 21,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Created ' . count($products) . ' sample products!');
    }
}

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
        $user = \App\Models\User::factory()->create(['id' => 1, 'name' => 'Test User', 'email' => 'testuser@example.com', 'password' => bcrypt('password123')]);
        \App\Models\Cart::factory()->create(['user_id' => $user->id]);
    }
}

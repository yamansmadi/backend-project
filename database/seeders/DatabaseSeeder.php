<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);


        $owner = \App\Models\User::create([
            'phone_number' => '0998887777',
            'first_name' => 'أحمد',
            'last_name' => 'البناء',
            'password' => bcrypt('password123'),
            'user_type' => 'owner',
            'status' => 'active'
        ]);

        // أنشئ شقة
        \App\Models\Apartment::create([
            'owner_id' => $owner->id,
            'title' => 'شقة فاخرة في دمشق',
            'city' => 'دمشق',
            'country' => 'سوريا',
            'price_per_night' => 150.00,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'is_active' => true
        ]);

        // المزيد من الشقق...
        \App\Models\Apartment::create([
            'owner_id' => $owner->id,
            'title' => 'شقة اقتصادية في حمص',
            'city' => 'حمص',
            'country' => 'سوريا',
            'price_per_night' => 80.00,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'is_active' => true
        ]);
    }
}

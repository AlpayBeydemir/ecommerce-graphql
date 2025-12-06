<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create addresses for test user
        Address::create([
            'user_id' => $user->id,
            'title' => 'Ev',
            'full_name' => 'Test User',
            'phone' => '+90 555 123 4567',
            'address_line_1' => 'Atatürk Caddesi No: 123',
            'address_line_2' => 'Daire 4',
            'city' => 'Istanbul',
            'state' => 'Istanbul',
            'postal_code' => '34000',
            'country' => 'Turkey',
            'is_default' => true,
        ]);

        Address::create([
            'user_id' => $user->id,
            'title' => 'İş',
            'full_name' => 'Test User',
            'phone' => '+90 555 987 6543',
            'address_line_1' => 'Maslak Mahallesi, İTÜ ARI 2',
            'address_line_2' => 'Kat 5, No: 34',
            'city' => 'Istanbul',
            'state' => 'Istanbul',
            'postal_code' => '34485',
            'country' => 'Turkey',
            'is_default' => false,
        ]);

        // Create another user
        $user2 = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        Address::create([
            'user_id' => $user2->id,
            'title' => 'Ev',
            'full_name' => 'Admin User',
            'phone' => '+90 555 111 2222',
            'address_line_1' => 'Bağdat Caddesi No: 456',
            'city' => 'Istanbul',
            'state' => 'Istanbul',
            'postal_code' => '34730',
            'country' => 'Turkey',
            'is_default' => true,
        ]);
    }
}

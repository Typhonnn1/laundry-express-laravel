<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Default Administrator
        User::create([
            'name'     => 'Administrator Laundry',
            'email'    => 'admin@laundry.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // 2. Akun Default Pelanggan (Customer)
        User::create([
            'name'     => 'Budi Pelanggan',
            'email'    => 'customer@gmail.com',
            'password' => Hash::make('customer123'),
            'role'     => 'customer',
        ]);

        // 3. Panggil Seeder Paket Laundry
        $this->call([
            LaundrySeeder::class,
        ]);
    }
}
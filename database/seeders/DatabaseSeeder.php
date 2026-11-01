<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Default Admin Account
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Laundry',
                'email' => 'admin@laundry.test',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'remember_token' => Str::random(10),
            ]
        );

        // 2. 6 Master Service Packages
        $servicesData = [
            [
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
                'description' => 'Cuci bersih, pengeringan higienis, dan setrika rapi untuk pakaian sehari-hari. Estimasi 2-3 hari.',
            ],
            [
                'service_name' => 'Cuci Kering Lipat Cepat',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
                'description' => 'Cuci dan kering bersih dilipat rapi tanpa setrika. Praktis dan hemat untuk pakaian santai. Estimasi 1-2 hari.',
            ],
            [
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
                'description' => 'Layanan prioritas selesai dalam 3 jam. Cuci, kering, dan setrika siap pakai.',
            ],
            [
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
                'description' => 'Pencucian mendalam bed cover ukuran king/queen dengan deterjen antibakteri dan pelembut khusus.',
            ],
            [
                'service_name' => 'Jas / Blazer Dry Cleaning',
                'category' => 'Satuan',
                'price_per_unit' => 35000,
                'unit_type' => 'Pcs',
                'description' => 'Perawatan dry cleaning profesional untuk pakaian formal, menjaga serat kain dan bentuk jas tetap presisi.',
            ],
            [
                'service_name' => 'Selimut Tebal Bulu Halus',
                'category' => 'Satuan',
                'price_per_unit' => 20000,
                'unit_type' => 'Pcs',
                'description' => 'Pencucian lembut khusus selimut tebal dan bulu halus agar tetap empuk, wangi, dan bebas tungau.',
            ],
        ];

        $services = [];
        foreach ($servicesData as $data) {
            $services[$data['service_name']] = Service::updateOrCreate(
                ['service_name' => $data['service_name']],
                $data
            );
        }

        // 3. Sample Initial Order: Siti Rahmawati
        $sitiOrder = Order::updateOrCreate(
            [
                'customer_name' => 'Siti Rahmawati',
                'whatsapp_number' => '081234567890',
            ],
            [
                'total_price' => 53000,
                'status' => 'Dicuci',
            ]
        );

        $sitiOrder->orderDetails()->delete();
        $sitiOrder->orderDetails()->create([
            'service_id' => $services['Cuci Komplit Reguler']->id,
            'qty_or_weight' => 4,
            'subtotal' => 28000, // 4 * 7.000
        ]);
        $sitiOrder->orderDetails()->create([
            'service_id' => $services['Bed Cover King Size']->id,
            'qty_or_weight' => 1,
            'subtotal' => 25000, // 1 * 25.000
        ]);

        // 4. Sample Initial Order: Budi Santoso
        $budiOrder = Order::updateOrCreate(
            [
                'customer_name' => 'Budi Santoso',
                'whatsapp_number' => '089876543210',
            ],
            [
                'total_price' => 115000,
                'status' => 'Selesai',
            ]
        );

        $budiOrder->orderDetails()->delete();
        $budiOrder->orderDetails()->create([
            'service_id' => $services['Cuci Kilat Express 3 Jam']->id,
            'qty_or_weight' => 3,
            'subtotal' => 45000, // 3 * 15.000
        ]);
        $budiOrder->orderDetails()->create([
            'service_id' => $services['Jas / Blazer Dry Cleaning']->id,
            'qty_or_weight' => 2,
            'subtotal' => 70000, // 2 * 35.000
        ]);
    }
}

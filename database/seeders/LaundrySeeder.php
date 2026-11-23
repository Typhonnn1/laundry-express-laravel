<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LaundrySeeder extends Seeder
{
    /**
     * Jalankan penyemaian data paket laundry awal.
     */
    public function run(): void
    {
        DB::table('laundry_packages')->insert([
            [
                'name'               => 'Cuci Komplit Reguler',
                'price_per_kg'       => 7000,
                'estimated_duration' => '2 Hari',
                'description'        => 'Pencucian bersih menyeluruh, detergen ramah serat kain, pewangi tahan lama, dan setrika rapi.',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuci Kering Kilat',
                'price_per_kg'       => 10000,
                'estimated_duration' => '1 Hari',
                'description'        => 'Layanan ekspres kering siap pakai dalam 24 jam tanpa antre lama.',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Setrika Saja',
                'price_per_kg'       => 5000,
                'estimated_duration' => '1 Hari',
                'description'        => 'Pakaian sudah dicuci dari rumah, disetrika licin dan disemprot pewangi premium.',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Cuci Bedcover / Selimut',
                'price_per_kg'       => 15000,
                'estimated_duration' => '3 Hari',
                'description'        => 'Pencucian khusus selimut tebal dan bedcover besar, higienis bebas tungau debu.',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();                                                                             // Nomor Nota Transaksi Otomatis (#1, #2, dst.)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');                  // Relasi akun pemesan ke tabel users
            $table->foreignId('package_id')->constrained('laundry_packages')->onDelete('cascade');   // Relasi paket ke tabel laundry_packages
            $table->string('customer_name');                                                          // Nama pelanggan pemesan
            $table->decimal('weight', 8, 2);                                                          // Berat cucian dalam Kg (contoh: 3.50 kg)
            $table->integer('total_price');                                                           // Total tagihan rupiah (backend: weight * price_per_kg)
            $table->enum('status', ['Baru', 'Diproses', 'Selesai', 'Diambil'])->default('Baru');     // Status progres pengerjaan cucian
            $table->date('order_date');                                                               // Tanggal transaksi masuk
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
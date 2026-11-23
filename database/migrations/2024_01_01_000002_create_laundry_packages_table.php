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
        Schema::create('laundry_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // Nama Paket Laundry (misal: Cuci Komplit Reguler)
            $table->integer('price_per_kg');             // Harga tarif per kilogram (Rp)
            $table->string('estimated_duration');        // Estimasi waktu pengerjaan (misal: 2 Hari, 1 Hari)
            $table->text('description');                 // Deskripsi detail fasilitas paket
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_packages');
    }
};
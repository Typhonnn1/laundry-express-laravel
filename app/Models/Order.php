<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Kolom yang dijaga dari mass assignment.
     * Alternatifnya menggunakan $fillable = ['user_id', 'package_id', 'customer_name', 'weight', 'total_price', 'status', 'order_date'];
     */
    protected $guarded = ['id'];

    /**
     * Casting tipe data kolom tanggal dan berat.
     */
    protected $casts = [
        'order_date' => 'date',
        'weight'     => 'float',
    ];

    /**
     * Relasi Many-to-One: Pesanan dimiliki oleh 1 akun pengguna pemesan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi Many-to-One: Pesanan mengacu ke 1 Paket Layanan Laundry.
     */
    public function package()
    {
        return $this->belongsTo(LaundryPackage::class, 'package_id');
    }
}
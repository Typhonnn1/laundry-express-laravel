<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryPackage extends Model
{
    use HasFactory;

    /**
     * Kolom yang dijaga dari mass assignment (id saja, kolom lain bebas diisi).
     * Anda juga dapat mendefinisikan array $fillable = ['name', 'price_per_kg', 'estimated_duration', 'description'];
     */
    protected $guarded = ['id'];

    /**
     * Relasi One-to-Many: 1 Paket Laundry dapat dipesan di banyak transaksi order.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'package_id');
    }
}
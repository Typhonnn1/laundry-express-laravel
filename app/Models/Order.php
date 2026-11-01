<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_name',
        'whatsapp_number',
        'total_price',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'total_price' => 0,
        'status' => self::STATUS_ANTREAN,
    ];

    /**
     * Status constants.
     */
    public const STATUS_ANTREAN = 'Antrean';

    public const STATUS_DICUCI = 'Dicuci';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUSES = [
        self::STATUS_ANTREAN,
        self::STATUS_DICUCI,
        self::STATUS_SELESAI,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_price' => 'integer',
        ];
    }

    /**
     * Get the order details associated with the order.
     */
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    /**
     * Formatted total price accessor (e.g. "Rp 35.000").
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp '.number_format($this->total_price, 0, ',', '.');
    }

    /**
     * Status badge styling for Bootstrap 5.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ANTREAN => 'warning text-dark',
            self::STATUS_DICUCI => 'info text-dark',
            self::STATUS_SELESAI => 'success',
            default => 'secondary',
        };
    }

    /**
     * Tracking progress percentage (0 - 100%).
     */
    public function getProgressPercentageAttribute(): int
    {
        return match ($this->status) {
            self::STATUS_ANTREAN => 33,
            self::STATUS_DICUCI => 66,
            self::STATUS_SELESAI => 100,
            default => 0,
        };
    }

    /**
     * Recalculate and update total_price based on related orderDetails.
     */
    public function recalculateTotalPrice(): void
    {
        $this->total_price = (int) $this->orderDetails()->sum('subtotal');
        $this->save();
    }
}

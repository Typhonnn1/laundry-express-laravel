<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_name',
        'category',
        'price_per_unit',
        'unit_type',
        'description',
    ];

    /**
     * Category constants.
     */
    public const CATEGORY_KILOAN = 'Kiloan';

    public const CATEGORY_SATUAN = 'Satuan';

    public const CATEGORY_EXPRESS = 'Express';

    public const CATEGORIES = [
        self::CATEGORY_KILOAN,
        self::CATEGORY_SATUAN,
        self::CATEGORY_EXPRESS,
    ];

    /**
     * Unit type constants.
     */
    public const UNIT_KG = 'Kg';

    public const UNIT_PCS = 'Pcs';

    public const UNIT_TYPES = [
        self::UNIT_KG,
        self::UNIT_PCS,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_unit' => 'integer',
        ];
    }

    /**
     * Get the order details associated with the service.
     */
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'service_id');
    }

    /**
     * Formatted price accessor (e.g. "Rp 7.000").
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format($this->price_per_unit, 0, ',', '.');
    }

    /**
     * Formatted rate accessor with unit (e.g. "Rp 7.000 / Kg").
     */
    public function getFormattedRateAttribute(): string
    {
        return $this->formatted_price.' / '.$this->unit_type;
    }
}

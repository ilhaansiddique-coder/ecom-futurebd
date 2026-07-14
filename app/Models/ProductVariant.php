<?php

namespace App\Models;

use App\Models\Concerns\HasStringPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;
    use HasStringPrimaryKey;

    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'product_id',
        'size',
        'color',
        'color_hex',
        'image',
        'price',
        'sale_price',
        'stock',
        'sku',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'sort_order' => 'integer',
    ];

    public function getInStockAttribute(): bool
    {
        return (int) $this->stock > 0;
    }

    public function getLowStockAttribute(): bool
    {
        return (int) $this->stock > 0 && (int) $this->stock <= self::LOW_STOCK_THRESHOLD;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

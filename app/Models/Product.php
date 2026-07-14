<?php

namespace App\Models;

use App\Models\Concerns\HasStringPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use HasStringPrimaryKey;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'sale_price',
        'stock',
        'status',
        'category_id',
        'brand_id',
        'images',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'images' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * When a product has variants its stock is the sum of its variants'
     * stock. No-op for products without variants (their stock is managed
     * directly through the inventory system).
     */
    public function syncStockFromVariants(): void
    {
        if ($this->hasVariants()) {
            $this->forceFill(['stock' => (int) $this->variants()->sum('stock')])->save();
        }
    }

    public function flashDeals(): BelongsToMany
    {
        return $this->belongsToMany(FlashDeal::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}

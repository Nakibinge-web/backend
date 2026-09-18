<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'barcode',
        'unit',
        'category_id',
        'supplier_id',
        'stock',
        'cost_price',
        'price',
        'reorder_level',
        'image_path',
        'description',
        'track_expiry',
        'manufacture_date',
        'expiry_date',
    ];

    protected $casts = [
        'track_expiry' => 'boolean',
    ];

    protected $appends = ['image_url'];

    // Computed full URL for the product image
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        return Storage::disk('public')->url($this->image_path);
    }

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_items')
                    ->withPivot('id', 'tenant_id', 'quantity', 'price', 'subtotal')
                    ->withTimestamps();
    }

    public function purchases()
    {
        return $this->belongsToMany(Purchase::class, 'purchase_items')
                    ->withPivot('id', 'tenant_id', 'quantity', 'cost_price')
                    ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Purchase extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'total_amount',
        'purchase_date',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'purchase_items')
                    ->withPivot('id', 'tenant_id', 'quantity', 'cost_price')
                    ->withTimestamps();
    }
}

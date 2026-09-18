<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class PurchaseItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'purchase_id',
        'product_id',
        'quantity',
        'cost_price',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

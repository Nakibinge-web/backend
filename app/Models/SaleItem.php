<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class SaleItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

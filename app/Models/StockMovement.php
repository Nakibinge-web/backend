<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class StockMovement extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'type',
        'quantity',
        'reference_id',
        'reference_type',
        'reason',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

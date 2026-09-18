<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Sale extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'customer_id',
        'discount_type',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'notes',
        'sale_date',
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'sale_items')
                    ->withPivot('id', 'tenant_id', 'quantity', 'price', 'subtotal')
                    ->withTimestamps();
    }
}

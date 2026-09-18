<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'source_ref',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'payment_status',
        'items',
        'notes',
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'due_date'        => 'date',
        'items'           => 'array',
        'subtotal'        => 'float',
        'discount_amount' => 'float',
        'tax_amount'      => 'float',
        'total_amount'    => 'float',
        'amount_paid'     => 'float',
    ];

    protected $attributes = [
        'items' => '[]',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

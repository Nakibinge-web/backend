<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Customer extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'display_name',
        'group',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Permissions visible to a tenant = default + their own custom ones
    public function scopeVisibleTo(Builder $query, int $tenantId): Builder
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')
              ->orWhere('tenant_id', $tenantId);
        });
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')->withTimestamps();
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

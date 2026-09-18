<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Roles visible to a tenant = default roles + their own custom roles
    public function scopeVisibleTo(Builder $query, int $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')         // default roles
              ->orWhere('tenant_id', $tenantId); // tenant's custom roles
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

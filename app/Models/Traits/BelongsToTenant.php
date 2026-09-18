<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Auto-apply tenant scope on all queries
        static::addGlobalScope(new TenantScope());

        // Auto-fill tenant_id on creation
        static::creating(function ($model) {
            if (app()->has('current_tenant') && empty($model->tenant_id)) {
                $model->tenant_id = app('current_tenant')->id;
            }
        });
    }
}

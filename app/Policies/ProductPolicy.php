<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * ProductPolicy
 * 
 * Authorizes product operations based on role-based access control.
 * Grants full CRUD access to owner and manager roles only.
 * Enforces tenant isolation for all resource-specific operations.
 */
class ProductPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any products.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can view the product.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product being accessed
     * @return bool True if user is owner or manager and product belongs to user's tenant
     */
    public function view(User $user, Product $product): bool
    {
        return $this->belongsToTenant($user, $product)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can create products.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can update the product.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product being updated
     * @return bool True if user is owner or manager and product belongs to user's tenant
     */
    public function update(User $user, Product $product): bool
    {
        return $this->belongsToTenant($user, $product)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can delete the product.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product being deleted
     * @return bool True if user is owner or manager and product belongs to user's tenant
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->belongsToTenant($user, $product)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }
}

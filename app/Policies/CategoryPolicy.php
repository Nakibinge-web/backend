<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * CategoryPolicy
 * 
 * Authorizes category operations based on role-based access control.
 * Grants full CRUD access to owner and manager roles only.
 * Enforces tenant isolation for all resource-specific operations.
 */
class CategoryPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any categories.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can view the category.
     * 
     * @param User $user The authenticated user
     * @param Category $category The category being accessed
     * @return bool True if user is owner or manager and category belongs to user's tenant
     */
    public function view(User $user, Category $category): bool
    {
        return $this->belongsToTenant($user, $category)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can create categories.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can update the category.
     * 
     * @param User $user The authenticated user
     * @param Category $category The category being updated
     * @return bool True if user is owner or manager and category belongs to user's tenant
     */
    public function update(User $user, Category $category): bool
    {
        return $this->belongsToTenant($user, $category)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can delete the category.
     * 
     * @param User $user The authenticated user
     * @param Category $category The category being deleted
     * @return bool True if user is owner or manager and category belongs to user's tenant
     */
    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToTenant($user, $category)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }
}

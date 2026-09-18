<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * SupplierPolicy
 * 
 * Authorizes supplier operations based on role-based access control.
 * Grants full CRUD access to owner and manager roles only.
 * Enforces tenant isolation for all resource-specific operations.
 */
class SupplierPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any suppliers.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can view the supplier.
     * 
     * @param User $user The authenticated user
     * @param Supplier $supplier The supplier being accessed
     * @return bool True if user is owner or manager and supplier belongs to user's tenant
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $this->belongsToTenant($user, $supplier)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can create suppliers.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can update the supplier.
     * 
     * @param User $user The authenticated user
     * @param Supplier $supplier The supplier being updated
     * @return bool True if user is owner or manager and supplier belongs to user's tenant
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $this->belongsToTenant($user, $supplier)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can delete the supplier.
     * 
     * @param User $user The authenticated user
     * @param Supplier $supplier The supplier being deleted
     * @return bool True if user is owner or manager and supplier belongs to user's tenant
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->belongsToTenant($user, $supplier)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }
}

<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

/**
 * PurchasePolicy
 * 
 * Authorizes purchase operations based on role-based access control.
 * Grants full CRUD access to owner and manager roles only.
 * Enforces tenant isolation for all resource-specific operations.
 */
class PurchasePolicy extends BasePolicy
{
    /**
     * Determine if the user can view any purchases.
     * Allows owners/managers by role, or users whose role has 'purchases.view'.
     *
     * @param User $user The authenticated user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoleOrPermission($user, ['owner', 'manager'], 'purchases.view');
    }

    /**
     * Determine if the user can view the purchase.
     * Allows owners/managers by role, or users whose role has 'purchases.view'.
     *
     * @param User $user The authenticated user
     * @param Purchase $purchase The purchase being accessed
     * @return bool
     */
    public function view(User $user, Purchase $purchase): bool
    {
        return $this->belongsToTenant($user, $purchase)
            && $this->hasRoleOrPermission($user, ['owner', 'manager'], 'purchases.view');
    }

    /**
     * Determine if the user can create purchases.
     * Allows owners/managers by role, or users whose role has 'purchases.create'.
     *
     * @param User $user The authenticated user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->hasRoleOrPermission($user, ['owner', 'manager'], 'purchases.create');
    }

    /**
     * Determine if the user can update the purchase.
     * 
     * Grants access to owners/managers by role, or to any user whose role
     * has the 'purchases.edit' permission assigned.
     * 
     * @param User $user The authenticated user
     * @param Purchase $purchase The purchase being updated
     * @return bool
     */
    public function update(User $user, Purchase $purchase): bool
    {
        return $this->belongsToTenant($user, $purchase)
            && $this->hasRoleOrPermission($user, ['owner', 'manager'], 'purchases.edit');
    }

    /**
     * Determine if the user can delete the purchase.
     * 
     * Grants access to owners/managers by role, or to any user whose role
     * has the 'purchases.delete' permission assigned.
     * 
     * @param User $user The authenticated user
     * @param Purchase $purchase The purchase being deleted
     * @return bool
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->belongsToTenant($user, $purchase)
            && $this->hasRoleOrPermission($user, ['owner', 'manager'], 'purchases.delete');
    }
}

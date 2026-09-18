<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

/**
 * SalePolicy - Authorization policy for Sale resources
 * 
 * This policy implements special authorization rules for sales:
 * - Owners have full CRUD access
 * - Managers have full CRUD access
 * - Cashiers can view and create sales only (no update/delete)
 * - Admins have no access to sales
 * 
 * All operations enforce tenant isolation before role checks.
 */
class SalePolicy extends BasePolicy
{
    /**
     * Determine if the user can view any sales.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner, manager, or cashier
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager', 'cashier']);
    }

    /**
     * Determine if the user can view the sale.
     * 
     * @param User $user The authenticated user
     * @param Sale $sale The sale being accessed
     * @return bool True if user is owner, manager, or cashier AND sale belongs to user's tenant
     */
    public function view(User $user, Sale $sale): bool
    {
        if (!$this->belongsToTenant($user, $sale)) {
            return false;
        }

        return $this->hasAnyRole($user, ['owner', 'manager', 'cashier']);
    }

    /**
     * Determine if the user can create sales.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner, manager, or cashier
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager', 'cashier']);
    }

    /**
     * Determine if the user can update the sale.
     *
     * Owners and managers always have access. Users with the 'sales.edit'
     * permission can also edit, but only their own sales.
     *
     * @param User $user The authenticated user
     * @param Sale $sale The sale being updated
     * @return bool
     */
    public function update(User $user, Sale $sale): bool
    {
        if (!$this->belongsToTenant($user, $sale)) {
            return false;
        }

        $isPrivileged = $this->hasAnyRole($user, ['owner', 'manager']);

        if ($isPrivileged) {
            return true;
        }

        // Users with sales.edit can only edit their own sales
        return $user->hasPermission('sales.edit') && $sale->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the sale.
     *
     * Owners and managers always have access. Users with the 'sales.delete'
     * permission can also delete, but only their own sales.
     *
     * @param User $user The authenticated user
     * @param Sale $sale The sale being deleted
     * @return bool
     */
    public function delete(User $user, Sale $sale): bool
    {
        if (!$this->belongsToTenant($user, $sale)) {
            return false;
        }

        $isPrivileged = $this->hasAnyRole($user, ['owner', 'manager']);

        if ($isPrivileged) {
            return true;
        }

        // Users with sales.delete can only delete their own sales
        return $user->hasPermission('sales.delete') && $sale->user_id === $user->id;
    }
}

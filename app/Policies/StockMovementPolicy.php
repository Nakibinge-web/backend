<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

/**
 * StockMovementPolicy
 * 
 * Authorizes stock movement operations based on role-based access control.
 * Grants full CRUD access to owner and manager roles only.
 * Enforces tenant isolation for all resource-specific operations.
 */
class StockMovementPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any stock movements.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can view the stock movement.
     * 
     * @param User $user The authenticated user
     * @param StockMovement $stockMovement The stock movement being accessed
     * @return bool True if user is owner or manager and stock movement belongs to user's tenant
     */
    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $this->belongsToTenant($user, $stockMovement)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can create stock movements.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or manager
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can update the stock movement.
     * 
     * @param User $user The authenticated user
     * @param StockMovement $stockMovement The stock movement being updated
     * @return bool True if user is owner or manager and stock movement belongs to user's tenant
     */
    public function update(User $user, StockMovement $stockMovement): bool
    {
        return $this->belongsToTenant($user, $stockMovement)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }

    /**
     * Determine if the user can delete the stock movement.
     * 
     * @param User $user The authenticated user
     * @param StockMovement $stockMovement The stock movement being deleted
     * @return bool True if user is owner or manager and stock movement belongs to user's tenant
     */
    public function delete(User $user, StockMovement $stockMovement): bool
    {
        return $this->belongsToTenant($user, $stockMovement)
            && $this->hasAnyRole($user, ['owner', 'manager']);
    }
}

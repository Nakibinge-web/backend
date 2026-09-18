<?php

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy - Authorization policy for User resources
 * 
 * This policy implements authorization rules for user management:
 * - Owners have full CRUD access to users
 * - Admins have full CRUD access to users
 * - Managers have no access to user management
 * - Cashiers have no access to user management
 * 
 * All operations enforce tenant isolation before role checks.
 */
class UserPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any users.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or admin
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'admin']);
    }

    /**
     * Determine if the user can view the specific user.
     * 
     * @param User $user The authenticated user
     * @param User $targetUser The user being accessed
     * @return bool True if user is owner or admin AND target user belongs to user's tenant
     */
    public function view(User $user, User $targetUser): bool
    {
        if (!$this->belongsToTenant($user, $targetUser)) {
            return false;
        }

        return $this->hasAnyRole($user, ['owner', 'admin']);
    }

    /**
     * Determine if the user can create users.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is owner or admin
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['owner', 'admin']);
    }

    /**
     * Determine if the user can update the specific user.
     * 
     * @param User $user The authenticated user
     * @param User $targetUser The user being updated
     * @return bool True if user is owner or admin AND target user belongs to user's tenant
     */
    public function update(User $user, User $targetUser): bool
    {
        if (!$this->belongsToTenant($user, $targetUser)) {
            return false;
        }

        return $this->hasAnyRole($user, ['owner', 'admin']);
    }

    /**
     * Determine if the user can delete the specific user.
     * 
     * @param User $user The authenticated user
     * @param User $targetUser The user being deleted
     * @return bool True if user is owner or admin AND target user belongs to user's tenant
     */
    public function delete(User $user, User $targetUser): bool
    {
        if (!$this->belongsToTenant($user, $targetUser)) {
            return false;
        }

        return $this->hasAnyRole($user, ['owner', 'admin']);
    }
}

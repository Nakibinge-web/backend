<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy class providing common helper methods for all policies.
 * 
 * This class establishes the tenant-first checking pattern where tenant
 * isolation is verified before role-based permissions.
 */
abstract class BasePolicy
{
    /**
     * Verify that a resource belongs to the user's tenant.
     * 
     * This method enforces tenant isolation by checking that the resource's
     * tenant_id matches the user's tenant_id. This check must pass before
     * any role-based authorization is evaluated.
     * 
     * @param User $user The authenticated user
     * @param Model $resource The model instance being accessed
     * @return bool True if resource belongs to user's tenant, false otherwise
     */
    protected function belongsToTenant(User $user, Model $resource): bool
    {
        return $resource->tenant_id !== null 
            && $user->tenant_id !== null 
            && $resource->tenant_id === $user->tenant_id;
    }

    /**
     * Check if the user has any of the specified roles.
     * 
     * This method checks if the user has at least one of the provided roles.
     * It uses the User model's hasRole() method which queries the role_user
     * pivot table.
     * 
     * @param User $user The authenticated user
     * @param array $roles Array of role names to check
     * @return bool True if user has at least one of the roles, false otherwise
     */
    protected function hasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user passes the role check OR holds a specific permission.
     *
     * This allows tenants with custom roles to be granted fine-grained
     * access (e.g. purchases.edit) without needing a full manager/owner role.
     *
     * @param User   $user       The authenticated user
     * @param array  $roles      Roles that automatically grant access
     * @param string $permission Permission name that also grants access
     * @return bool
     */
    protected function hasRoleOrPermission(User $user, array $roles, string $permission): bool
    {
        return $this->hasAnyRole($user, $roles) || $user->hasPermission($permission);
    }
}

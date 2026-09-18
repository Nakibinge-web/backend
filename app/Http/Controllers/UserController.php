<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List users in the current tenant.
     * - Owners / admins see everyone.
     * - Users with users.view permission see only users they created.
     * GET /api/users
     */
    public function index(): JsonResponse
    {
        $actor = Auth::user();
        $isPrivileged = $actor->roles()->pluck('name')
                              ->intersect(['owner', 'admin'])
                              ->isNotEmpty();

        if (!$isPrivileged && !$actor->hasPermission('users.view')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view users.'], 403);
        }

        $query = User::with('roles:id,name')->select('id', 'name', 'email', 'created_by', 'created_at');

        // Non-privileged users only see the users they personally created
        if (!$isPrivileged) {
            $query->where('created_by', $actor->id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /**
     * Return the currently authenticated user's profile.
     * GET /api/users/me
     */
    public function me(): JsonResponse
    {
        $user = Auth::user()->load('roles.permissions:id,name', 'tenant:id,name,email,phone,address');

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * Create a new user within the current tenant.
     * POST /api/users  — requires owner/admin role OR users.create permission
     */
    public function store(Request $request): JsonResponse
    {
        $actor = Auth::user();
        $isPrivileged = $actor->roles()->pluck('name')
                              ->intersect(['owner', 'admin'])
                              ->isNotEmpty();

        if (!$isPrivileged && !$actor->hasPermission('users.create')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to create users.'], 403);
        }

        $tenantId = $actor->tenant_id;

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
            'role_ids'   => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'password'   => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        // Ensure all assigned roles are visible to this tenant
        $validRoleIds = Role::visibleTo($tenantId)
                            ->whereIn('id', $validated['role_ids'])
                            ->pluck('id');

        if ($validRoleIds->count() !== count($validated['role_ids'])) {
            return response()->json(['success' => false, 'message' => 'One or more roles are not accessible.'], 422);
        }

        // Prevent non-owners from assigning the owner role
        $ownerRole = Role::where('name', 'owner')->whereNull('tenant_id')->first();
        $actorIsOwner = $actor->hasRole('owner');

        if ($ownerRole && in_array($ownerRole->id, $validated['role_ids']) && !$actorIsOwner) {
            return response()->json(['success' => false, 'message' => 'Only owners can assign the owner role.'], 403);
        }

        // Non-privileged users (permission-based) cannot assign owner or admin roles
        if (!$isPrivileged) {
            $privilegedRoles = Role::whereIn('name', ['owner', 'admin'])->pluck('id')->toArray();
            foreach ($validated['role_ids'] as $rid) {
                if (in_array($rid, $privilegedRoles)) {
                    return response()->json(['success' => false, 'message' => 'You cannot assign owner or admin roles.'], 403);
                }
            }
        }

        $user = User::create([
            'tenant_id'  => $tenantId,
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'created_by' => $actor->id,
        ]);

        $user->roles()->attach($validRoleIds);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->get(['roles.id', 'name']),
            ],
        ], 201);
    }

    /**
     * Atomically create a user with a brand-new custom role and selected permissions.
     * POST /api/users/with-custom-role  — requires owner role
     */
    public function storeWithCustomRole(Request $request): JsonResponse
    {
        $tenantId     = Auth::user()->tenant_id;
        $actorIsOwner = Auth::user()->hasRole('owner');

        if (!$actorIsOwner) {
            return response()->json(['success' => false, 'message' => 'Only owners can create users with custom roles.'], 403);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255|unique:users,email',
            'password'          => ['required', Password::min(8)->mixedCase()->numbers()],
            'role_name'         => 'required|string|max:255',
            'role_description'  => 'nullable|string|max:500',
            'permission_ids'    => 'required|array|min:1',
            'permission_ids.*'  => 'exists:permissions,id',
        ]);

        // Prevent name clash with default or existing tenant roles
        $roleExists = Role::where('name', $validated['role_name'])
            ->where(fn($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->exists();

        if ($roleExists) {
            return response()->json(['success' => false, 'message' => 'A role with this name already exists.'], 422);
        }

        // Ensure all permission_ids are visible to this tenant
        $validPermIds = Permission::visibleTo($tenantId)
            ->whereIn('id', $validated['permission_ids'])
            ->pluck('id');

        if ($validPermIds->count() !== count($validated['permission_ids'])) {
            return response()->json(['success' => false, 'message' => 'One or more permissions are not accessible.'], 422);
        }

        return DB::transaction(function () use ($validated, $tenantId, $validPermIds) {
            // 1. Create the custom role
            $role = Role::create([
                'tenant_id'   => $tenantId,
                'name'        => $validated['role_name'],
                'description' => $validated['role_description'] ?? null,
                'is_default'  => false,
            ]);

            // 2. Attach permissions to the role
            $role->permissions()->attach($validPermIds);

            // 3. Create the user
            $user = User::create([
                'tenant_id' => $tenantId,
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
            ]);

            // 4. Attach the new role to the user
            $user->roles()->attach($role->id);

            $role->load('permissions:id,name,display_name,group');

            return response()->json([
                'success' => true,
                'message' => 'User created successfully with custom role.',
                'data'    => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'created_at' => $user->created_at,
                    'roles'      => $user->roles()->get(['roles.id', 'name']),
                    'custom_role' => $role,
                ],
            ], 201);
        });
    }

    /**
     * Update the permissions assigned to a custom role belonging to this tenant.
     * PUT /api/users/{user}/custom-role-permissions — requires owner role
     */
    public function updateCustomRolePermissions(Request $request, User $user): JsonResponse
    {
        $tenantId     = Auth::user()->tenant_id;
        $actorIsOwner = Auth::user()->hasRole('owner');

        if (!$actorIsOwner) {
            return response()->json(['success' => false, 'message' => 'Only owners can modify custom role permissions.'], 403);
        }

        $validated = $request->validate([
            'role_id'           => 'required|exists:roles,id',
            'permission_ids'    => 'required|array|min:1',
            'permission_ids.*'  => 'exists:permissions,id',
        ]);

        // Ensure the role is a custom role belonging to this tenant (not a default role)
        $role = Role::where('id', $validated['role_id'])
            ->where('tenant_id', $tenantId)
            ->where('is_default', false)
            ->first();

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found or cannot be modified (default roles are protected).'], 422);
        }

        // Ensure the user actually has this role
        if (!$user->roles()->where('roles.id', $role->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This user does not have the specified role.'], 422);
        }

        // Validate all permission_ids are visible to this tenant
        $validPermIds = Permission::visibleTo($tenantId)
            ->whereIn('id', $validated['permission_ids'])
            ->pluck('id');

        if ($validPermIds->count() !== count($validated['permission_ids'])) {
            return response()->json(['success' => false, 'message' => 'One or more permissions are not accessible.'], 422);
        }

        // Sync (replace) the permissions on this role
        $role->permissions()->sync($validPermIds);

        $role->load('permissions:id,name,display_name,group');

        return response()->json([
            'success' => true,
            'message' => 'Custom role permissions updated successfully.',
            'data'    => [
                'role'        => $role,
                'user'        => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'permissions' => $role->permissions,
            ],
        ]);
    }

    /**
     * Show a specific user (must belong to same tenant).
     * GET /api/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles.permissions:id,name,display_name,group', 'tenant:id,name,email,phone,address');

        // Annotate which roles are custom (tenant-owned, non-default)
        $roles = $user->roles->map(function ($role) use ($user) {
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'description' => $role->description,
                'is_default'  => $role->is_default,
                'is_custom'   => !$role->is_default && $role->tenant_id !== null,
                'permissions' => $role->permissions,
            ];
        });

        return response()->json(['success' => true, 'data' => [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'roles'      => $roles,
            'tenant'     => $user->tenant->name,
            'created_at' => $user->created_at,
        ]]);
    }

    /**
     * Update a user's details.
     * PUT /api/users/{user}  — requires owner/admin role OR users.edit permission
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $actor = Auth::user();
        $isPrivileged = $actor->roles()->pluck('name')
                              ->intersect(['owner', 'admin'])
                              ->isNotEmpty();

        // Permission check: privileged always allowed; others need users.edit
        // and can only edit users they created
        if (!$isPrivileged) {
            if (!$actor->hasPermission('users.edit')) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to edit users.'], 403);
            }
            if ($user->created_by !== $actor->id) {
                return response()->json(['success' => false, 'message' => 'You can only edit users you created.'], 403);
            }
        }

        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'password' => ['sometimes', 'nullable', Password::min(8)->mixedCase()->numbers()],
            'role_ids' => 'sometimes|array|min:1',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Handle role updates if provided
        if (isset($validated['role_ids'])) {
            $tenantId = Auth::user()->tenant_id;

            $validRoleIds = Role::visibleTo($tenantId)
                                ->whereIn('id', $validated['role_ids'])
                                ->pluck('id');

            if ($validRoleIds->count() !== count($validated['role_ids'])) {
                return response()->json(['success' => false, 'message' => 'One or more roles are not accessible.'], 422);
            }

            $ownerRole = Role::where('name', 'owner')->whereNull('tenant_id')->first();
            $actorIsOwner = Auth::user()->hasRole('owner');

            if ($ownerRole && in_array($ownerRole->id, $validated['role_ids']) && !$actorIsOwner) {
                return response()->json(['success' => false, 'message' => 'Only owners can assign the owner role.'], 403);
            }

            $user->roles()->sync($validRoleIds);
            unset($validated['role_ids']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->get(['roles.id', 'name']),
            ],
        ]);
    }

    /**
     * Delete a user from the current tenant.
     * DELETE /api/users/{user}  — requires owner/admin role OR users.delete permission
     */
    public function destroy(User $user): JsonResponse
    {
        $actor = Auth::user();
        $isPrivileged = $actor->roles()->pluck('name')
                              ->intersect(['owner', 'admin'])
                              ->isNotEmpty();

        if (!$isPrivileged) {
            if (!$actor->hasPermission('users.delete')) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to delete users.'], 403);
            }
            if ($user->created_by !== $actor->id) {
                return response()->json(['success' => false, 'message' => 'You can only delete users you created.'], 403);
            }
        }

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        // Prevent deleting another owner unless you are also an owner
        if ($user->hasRole('owner') && !Auth::user()->hasRole('owner')) {
            return response()->json(['success' => false, 'message' => 'Only owners can remove other owners.'], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    /**
     * Filter users by role name.
     * GET /api/users/by-role?role=cashier
     */
    public function getUsersByRole(Request $request): JsonResponse
    {
        $validated = $request->validate(['role' => 'required|string']);

        $users = User::whereHas('roles', fn($q) => $q->where('name', $validated['role']))
                     ->with('roles:id,name')
                     ->select('id', 'name', 'email')
                     ->get();

        return response()->json(['success' => true, 'data' => $users]);
    }
}

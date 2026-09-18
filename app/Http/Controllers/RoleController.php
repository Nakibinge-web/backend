<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * List all roles visible to the current tenant
     * (default system roles + tenant's own custom roles).
     */
    public function index(): JsonResponse
    {
        $roles = Role::visibleTo(Auth::user()->tenant_id)->get();

        return response()->json(['success' => true, 'data' => $roles]);
    }

    /**
     * Create a custom role for the current tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Prevent duplicate name within this tenant or clashing with a default role
        $exists = Role::where('name', $request->name)
                      ->where(fn($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
                      ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A role with this name already exists.'], 422);
        }

        $role = Role::create([
            'tenant_id'   => $tenantId,
            'name'        => $request->name,
            'description' => $request->description,
            'is_default'  => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Role created successfully.', 'data' => $role], 201);
    }

    /**
     * Show a single role (must be visible to the tenant).
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorizeRole($role);

        $role->load('users:id,name,email');

        return response()->json(['success' => true, 'data' => $role]);
    }

    /**
     * Update a custom role (default roles cannot be edited).
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorizeRole($role);

        if ($role->is_default) {
            return response()->json(['success' => false, 'message' => 'Default roles cannot be modified.'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role->update($request->only('name', 'description'));

        return response()->json(['success' => true, 'message' => 'Role updated successfully.', 'data' => $role]);
    }

    /**
     * Delete a custom role (default roles cannot be deleted).
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorizeRole($role);

        if ($role->is_default) {
            return response()->json(['success' => false, 'message' => 'Default roles cannot be deleted.'], 403);
        }

        $role->delete();

        return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
    }

    /**
     * Create a custom role and assign permissions to it in one shot.
     * POST /api/roles/with-permissions
     * Body: { name, description?, permission_ids[] }
     */
    public function storeWithPermissions(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'permission_ids'    => 'nullable|array',
            'permission_ids.*'  => 'exists:permissions,id',
        ]);

        // Prevent name clashing with default or existing tenant roles
        $exists = Role::where('name', $request->name)
                      ->where(fn($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
                      ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A role with this name already exists.'], 422);
        }

        $role = Role::create([
            'tenant_id'   => $tenantId,
            'name'        => $request->name,
            'description' => $request->description,
            'is_default'  => false,
        ]);

        if (!empty($request->permission_ids)) {
            $validIds = \App\Models\Permission::visibleTo($tenantId)
                                              ->whereIn('id', $request->permission_ids)
                                              ->pluck('id');
            $role->permissions()->attach($validIds);
        }

        $role->load('permissions:id,name,display_name,group');

        return response()->json([
            'success' => true,
            'message' => 'Custom role created successfully.',
            'data'    => $role,
        ], 201);
    }
    public function assign(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'role_ids'   => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Ensure all roles are visible to this tenant
        $validRoleIds = Role::visibleTo($tenantId)->whereIn('id', $request->role_ids)->pluck('id');

        if ($validRoleIds->count() !== count($request->role_ids)) {
            return response()->json(['success' => false, 'message' => 'One or more roles are not accessible.'], 422);
        }

        $user = \App\Models\User::findOrFail($request->user_id);
        $user->roles()->syncWithoutDetaching($validRoleIds);

        return response()->json(['success' => true, 'message' => 'Roles assigned successfully.', 'data' => $user->roles]);
    }

    /**
     * Revoke a role from a user.
     * DELETE /api/roles/revoke  { user_id, role_id }
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $user->roles()->detach($request->role_id);

        return response()->json(['success' => true, 'message' => 'Role revoked successfully.']);
    }

    // Ensure the role is either a default or belongs to the current tenant
    private function authorizeRole(Role $role): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (!$role->is_default && $role->tenant_id !== $tenantId) {
            abort(403, 'This role does not belong to your tenant.');
        }
    }
}

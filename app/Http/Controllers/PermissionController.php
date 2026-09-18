<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    /**
     * List all permissions visible to the current tenant,
     * optionally grouped by their group field.
     * GET /api/permissions
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::visibleTo(Auth::user()->tenant_id)
                                 ->orderBy('group')
                                 ->orderBy('name')
                                 ->get()
                                 ->groupBy('group');

        return response()->json(['success' => true, 'data' => $permissions]);
    }

    /**
     * Create a custom permission for the current tenant.
     * POST /api/permissions
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'         => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'group'        => 'nullable|string|max:100',
        ]);

        $exists = Permission::where('name', $request->name)
                            ->where(fn($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
                            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A permission with this name already exists.'], 422);
        }

        $permission = Permission::create([
            'tenant_id'    => $tenantId,
            'name'         => $request->name,
            'display_name' => $request->display_name,
            'group'        => $request->group,
            'is_default'   => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Permission created successfully.', 'data' => $permission], 201);
    }

    /**
     * Show a single permission.
     * GET /api/permissions/{permission}
     */
    public function show(Permission $permission): JsonResponse
    {
        $this->authorizePermission($permission);

        $permission->load('roles:id,name');

        return response()->json(['success' => true, 'data' => $permission]);
    }

    /**
     * Update a custom permission (default permissions cannot be edited).
     * PUT /api/permissions/{permission}
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $this->authorizePermission($permission);

        if ($permission->is_default) {
            return response()->json(['success' => false, 'message' => 'Default permissions cannot be modified.'], 403);
        }

        $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'group'        => 'nullable|string|max:100',
        ]);

        $permission->update($request->only('name', 'display_name', 'group'));

        return response()->json(['success' => true, 'message' => 'Permission updated successfully.', 'data' => $permission]);
    }

    /**
     * Delete a custom permission (default permissions cannot be deleted).
     * DELETE /api/permissions/{permission}
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorizePermission($permission);

        if ($permission->is_default) {
            return response()->json(['success' => false, 'message' => 'Default permissions cannot be deleted.'], 403);
        }

        $permission->delete();

        return response()->json(['success' => true, 'message' => 'Permission deleted successfully.']);
    }

    /**
     * Assign permissions to a role.
     * POST /api/permissions/assign  { role_id, permission_ids: [] }
     */
    public function assign(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'role_id'           => 'required|exists:roles,id',
            'permission_ids'    => 'required|array|min:1',
            'permission_ids.*'  => 'exists:permissions,id',
        ]);

        // Ensure role belongs to this tenant
        $role = Role::visibleTo($tenantId)->findOrFail($request->role_id);

        // Ensure all permissions are visible to this tenant
        $validIds = Permission::visibleTo($tenantId)
                              ->whereIn('id', $request->permission_ids)
                              ->pluck('id');

        if ($validIds->count() !== count($request->permission_ids)) {
            return response()->json(['success' => false, 'message' => 'One or more permissions are not accessible.'], 422);
        }

        $role->permissions()->syncWithoutDetaching($validIds);

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned successfully.',
            'data'    => $role->permissions()->get(['permissions.id', 'name', 'display_name']),
        ]);
    }

    /**
     * Revoke a permission from a role.
     * DELETE /api/permissions/revoke  { role_id, permission_id }
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->validate([
            'role_id'       => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $role->permissions()->detach($request->permission_id);

        return response()->json(['success' => true, 'message' => 'Permission revoked successfully.']);
    }

    // Ensure the permission is either a default or belongs to the current tenant
    private function authorizePermission(Permission $permission): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (!$permission->is_default && $permission->tenant_id !== $tenantId) {
            abort(403, 'This permission does not belong to your tenant.');
        }
    }
}

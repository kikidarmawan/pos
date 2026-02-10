<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->can('view_roles') || abort(403, 'Forbidden');

        $roles = Role::with('permissions')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $request->user()->can('create_roles') || abort(403, 'Forbidden');

        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role berhasil ditambahkan',
            'role' => $role,
        ], 201);
    }

    public function show(Role $role)
    {
        request()->user()->can('view_roles') || abort(403, 'Forbidden');

        $role->load('permissions');

        return response()->json($role);
    }

    public function update(Request $request, Role $role)
    {
        $request->user()->can('edit_roles') || abort(403, 'Forbidden');

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role berhasil diperbarui',
            'role' => $role,
        ]);
    }

    public function destroy(Role $role)
    {
        request()->user()->can('delete_roles') || abort(403, 'Forbidden');

        $role->delete();

        return response()->json([
            'message' => 'Role berhasil dihapus',
        ]);
    }

    public function permissions()
    {
        request()->user()->can('view_roles') || abort(403, 'Forbidden');

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('_', $permission->name)[0];
        });

        return response()->json($permissions);
    }
}

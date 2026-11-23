<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccessControlController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get();

        $permissions = Permission::orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        $users = User::with('roles')
            ->orderBy('name')
            ->get();

        return view('dashboard.access.index', compact(
            'roles',
            'permissions',
            'users'
        ));
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                'unique:roles,name',
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Role::create($data);

        return back()->with('success', 'Role berhasil dibuat.');
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_if($role->name === 'super-admin', 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role->update($data);

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroyRole(Role $role)
    {
        abort_if($role->name === 'super-admin', 403);

        $role->delete();

        return back()->with('success', 'Role berhasil dihapus.');
    }

    public function updatePermissions(Request $request, Role $role)
    {
        abort_if($role->name === 'super-admin', 403);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Permission berhasil diperbarui.');
    }

    public function updateUserRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->roles()->sync($data['roles'] ?? []);
        });

        return back()->with('success', 'Role user berhasil diperbarui.');
    }
}

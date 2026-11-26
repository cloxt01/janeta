<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Services\Requests\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessControlController extends Controller
{
    protected AuditController $auditController;
    protected AccessControlService $accessControlService;

    public function __construct(
        AuditController $auditController,
        AccessControlService $accessControlService
    ) {
        $this->auditController = $auditController;
        $this->accessControlService = $accessControlService;
    }

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

        $users = User::with([
            'roles',
            'directPermissions',
        ])
            ->orderBy('name')
            ->get();

        return view('dashboard.management.access-control', compact(
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

        $role = Role::create($data);

        $this->auditController->audit(request(),
            'role.created',
            'Role dibuat.',
            $role
        );

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

        $this->auditController->audit(request(),
            'role.updated',
            'Role diupdate.',
            $role
        );

        return back()->with('success', 'Role berhasil diperbarui.');
    }
    public function destroyRole(Role $role)
    {
        abort_if($role->name === 'super-admin', 403);

        $role->delete();

        $this->auditController->audit(request(),
            'role.deleted',
            'Role dihapus.',
            $role
        );

        return back()->with('success', 'Role berhasil dihapus.');
    }

    public function updatePermissions(Request $request, Role $role)
    {
        abort_if($role->name === 'super-admin', 403);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->accessControlService->syncRolePermissions(
            $role,
            $data['permissions'] ?? []
        );

        $this->auditController->audit(
            $request,
            'permission.updated',
            'Permission role diupdate.',
            $role
        );

        return back()->with('success', 'Permission berhasil diperbarui.');
    }
    public function updateUserRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $this->accessControlService->syncUserRoles(
            $user,
            $data['roles'] ?? []
        );

        $this->auditController->audit(
            $request,
            'role.updated',
            'Role pengguna diupdate.',
            $user
        );

        return back()->with('success', 'Role user berhasil diperbarui.');
    }
    public function updateUserPermissions(Request $request, User $user)
    {
        if ($user->hasRole('super-admin')) {
            return back()->with(
                'error',
                'Permission khusus untuk super-admin tidak dapat diubah.'
            );
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'in:grant,deny,default'],
        ]);

        $this->accessControlService->syncUserPermissions(
            $user,
            $data['permissions'] ?? []
        );

        $this->auditController->audit(
            $request,
            'user.permission.updated',
            'Permission khusus pengguna diupdate.',
            $user
        );

        return back()->with(
            'success',
            'Permission khusus pengguna berhasil diperbarui.'
        );
    }
}

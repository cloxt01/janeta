<?php

namespace App\Services\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AccessControlService
{
    /**
     * Sinkronisasi permission milik role.
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds) {
            $role->permissions()->sync($permissionIds);
        });
    }

    /**
     * Sinkronisasi role milik user.
     */
    public function syncUserRoles(User $user, array $roleIds): void
    {
        DB::transaction(function () use ($user, $roleIds) {
            $user->roles()->sync($roleIds);
        });
    }

    /**
     * Mengatur direct permission user.
     *
     * grant   = memberikan izin langsung
     * deny    = menolak izin langsung
     * default = kembali mengikuti permission dari role
     */
    public function syncUserPermissions(User $user, array $permissions): void
    {
        if ($user->hasRole('super-admin')) {
            throw new \InvalidArgumentException(
                'Permission khusus untuk super-admin tidak dapat diubah.'
            );
        }

        DB::transaction(function () use ($user, $permissions) {
            $overrides = [];
            $defaults = [];

            foreach ($permissions as $permissionId => $effect) {
                if ($effect === 'default') {
                    $defaults[] = $permissionId;
                    continue;
                }

                $overrides[$permissionId] = [
                    'effect' => $effect,
                ];
            }

            // Hapus override agar kembali mengikuti role.
            if (!empty($defaults)) {
                $user->directPermissions()->detach($defaults);
            }

            // Tambahkan atau perbarui override grant/deny.
            if (!empty($overrides)) {
                $user->directPermissions()->syncWithoutDetaching($overrides);
            }
        });
    }
}

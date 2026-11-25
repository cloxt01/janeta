<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | PERMISSIONS
            |--------------------------------------------------------------------------
            */

            $permissionGroups = [

                'Dashboard' => [
                    'dashboard.view' => 'Melihat Dashboard',
                ],

                'ONT' => [
                    'ont.lookup'     => 'Mencari ONT',
                    'ont.processing' => 'Melihat Proses ONT',
                    'ont.control'    => 'Mengakses Kontrol ONT',
                    'ont.restart'    => 'Restart ONT',
                    'ont.activate'   => 'Aktifkan ONT',
                    'ont.deactivate' => 'Nonaktifkan ONT',
                ],

                'Activity' => [
                    'olt.activity.view'      => 'Melihat Aktivitas OLT',
                    'mikrotik.activity.view' => 'Melihat Aktivitas MikroTik',
                    'audit.view'             => 'Melihat Audit Logs',
                ],

                'OLT' => [
                    'olt.view'   => 'Melihat OLT',
                    'olt.create' => 'Menambahkan OLT',
                    'olt.update' => 'Mengubah OLT',
                    'olt.delete' => 'Menghapus OLT',
                    'olt.test'   => 'Testing Koneksi OLT',
                ],

                'Telegram' => [
                    'telegram.view'   => 'Melihat Whitelist Telegram',
                    'telegram.manage' => 'Mengelola Whitelist Telegram',
                ],

                'Users' => [
                    'users.view'   => 'Melihat Pengguna',
                    'users.create' => 'Menambahkan Pengguna',
                    'users.update' => 'Mengubah Pengguna',
                    'users.delete' => 'Menghapus Pengguna',
                ],

                'Access Control' => [
                    'access.view'   => 'Melihat Access Control',
                    'access.manage' => 'Mengelola Access Control',
                ],

                'Location' => [
                    'location.track'   => 'Dilacak Lokasinya (Wajib Izin GPS)',
                    'location.view'    => 'Melihat Live Location',
                    'location.history' => 'Melihat Riwayat Lokasi',
                ],

                'Router' => [
                    'router-mikrotik.view'    => 'Melihat Router MikroTik',
                    'router-mikrotik.show'    => 'Melihat Detail Router MikroTik',
                    'router-mikrotik.store'   => 'Menambahkan Router MikroTik',
                    'router-mikrotik.update'  => 'Mengubah Router MikroTik',
                    'router-mikrotik.destroy' => 'Menghapus Router MikroTik',
                    'router-mikrotik.capture' => 'Capture Data Router MikroTik',
                    'router-mikrotik.test'    => 'Testing Koneksi Router MikroTik',
                ],

            ];

            $permissionIds = [];

            foreach ($permissionGroups as $module => $permissions) {
                foreach ($permissions as $name => $displayName) {

                    $permission = Permission::updateOrCreate(
                        ['name' => $name],
                        [
                            'display_name' => $displayName,
                            'module'       => $module,
                            'description'  => $displayName,
                        ]
                    );

                    $permissionIds[$name] = $permission->id;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | ROLES
            |--------------------------------------------------------------------------
            */

            $roles = [

                'super-admin' => [
                    'display_name' => 'Super Admin',
                    'description'  => 'Memiliki seluruh hak akses JANETA.',
                ],

                'admin' => [
                    'display_name' => 'Admin',
                    'description'  => 'Mengelola operasional dan pengguna JANETA.',
                ],

                'operator' => [
                    'display_name' => 'Operator',
                    'description'  => 'Mengelola operasional OLT dan ONT.',
                ],

                'customer-care' => [
                    'display_name' => 'Customer Care',
                    'description'  => 'Mengakses informasi pelanggan dan ONT.',
                ],

                'viewer' => [
                    'display_name' => 'Viewer',
                    'description'  => 'Hanya memiliki hak akses melihat data.',
                ],

            ];

            $roleModels = [];

            foreach ($roles as $name => $data) {
                $roleModels[$name] = Role::updateOrCreate(
                    ['name' => $name],
                    $data
                );
            }


            /*
            |--------------------------------------------------------------------------
            | ROLE PERMISSIONS
            |--------------------------------------------------------------------------
            */

            $allPermissions = array_keys($permissionIds);

            $rolePermissions = [

                // Seluruh akses
                'super-admin' => $allPermissions,

                // Administrasi sistem
                'admin' => [
                    'dashboard.view',

                    // ONT
                    'ont.lookup',
                    'ont.processing',
                    'ont.control',
                    'ont.restart',
                    'ont.activate',
                    'ont.deactivate',

                    // Activity
                    'olt.activity.view',
                    'mikrotik.activity.view',
                    'audit.view',

                    // OLT Management
                    'olt.view',
                    'olt.create',
                    'olt.update',
                    'olt.delete',
                    'olt.test',

                    // Telegram
                    'telegram.view',
                    'telegram.manage',

                    // Users
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',

                    // Access Control
                    'access.view',
                    'access.manage',

                    // Location
                    'location.track',

                    // MikroTik Management
                    'router-mikrotik.view',
                    'router-mikrotik.show',
                    'router-mikrotik.store',
                    'router-mikrotik.update',
                    'router-mikrotik.destroy',
                    'router-mikrotik.capture',
                    'router-mikrotik.test',
                ],

                // Operasional jaringan
                'operator' => [
                    'dashboard.view',

                    // ONT
                    'ont.lookup',
                    'ont.processing',
                    'ont.control',
                    'ont.restart',

                    // Activity
                    'olt.activity.view',
                    'mikrotik.activity.view',

                    // OLT
                    'olt.view',
                    'olt.test',

                    // Location
                    'location.track',

                    // MikroTik
                    'router-mikrotik.view',
                    'router-mikrotik.show',
                ],

                // Customer Care
                'customer-care' => [
                    'dashboard.view',
                    'ont.lookup',
                    'ont.processing',

                    'location.track',
                ],

                // Read only
                'viewer' => [
                    'dashboard.view',
                    'ont.lookup',
                    'ont.processing',

                    // Activity
                    'olt.activity.view',

                    // OLT
                    'olt.view',

                    // Telegram
                    'telegram.view',

                    // Location
                    'location.track',
                ],

            ];

            foreach ($rolePermissions as $roleName => $permissions) {

                $ids = collect($permissions)
                    ->map(function ($permission) use ($permissionIds) {

                        if (!isset($permissionIds[$permission])) {
                            throw new \RuntimeException(
                                "Permission {$permission} belum terdaftar di AccessControlSeeder."
                            );
                        }

                        return $permissionIds[$permission];
                    })
                    ->all();

                $roleModels[$roleName]
                    ->permissions()
                    ->sync($ids);
            }


            /*
            |--------------------------------------------------------------------------
            | ASSIGN SUPER ADMIN
            |--------------------------------------------------------------------------
            |
            | Tentukan email akun Super Admin melalui .env.
            | Tidak otomatis memberikan akses kepada semua user.
            |
            */

            $superAdminEmail = env('JANETA_SUPER_ADMIN_EMAIL');

            if ($superAdminEmail) {

                $user = User::where('email', $superAdminEmail)->first();

                if (!$user) {
                    throw new \RuntimeException(
                        "User Super Admin dengan email {$superAdminEmail} tidak ditemukan."
                    );
                }

                $user->roles()->syncWithoutDetaching([
                    $roleModels['super-admin']->id,
                ]);

                $this->command->info(
                    "Super Admin berhasil diberikan kepada {$user->email}"
                );

            } else {

                $this->command->warn(
                    'JANETA_SUPER_ADMIN_EMAIL belum diatur. Role Super Admin belum diberikan kepada user.'
                );
            }

            $this->command->info('JANETA Access Control berhasil di-seed.');
            $this->command->info('Total permission: ' . count($permissionIds));
            $this->command->info('Total role: ' . count($roleModels));

        });
    }
}

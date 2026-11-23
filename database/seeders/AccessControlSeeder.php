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

                'OLT' => [
                    'olt.activity.view' => 'Melihat Aktivitas OLT',
                    'olt.view'           => 'Melihat OLT',
                    'olt.create'         => 'Menambahkan OLT',
                    'olt.update'         => 'Mengubah OLT',
                    'olt.delete'         => 'Menghapus OLT',
                    'olt.test'           => 'Testing Koneksi OLT',
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

                'Audit' => [
                    'audit.view' => 'Melihat Audit Logs',
                ],

                'Access Control' => [
                    'access.view'   => 'Melihat Access Control',
                    'access.manage' => 'Mengelola Access Control',
                ],
            ];

            $permissionIds = [];

            foreach ($permissionGroups as $module => $permissions) {
                foreach ($permissions as $name => $displayName) {

                    $permission = Permission::updateOrCreate(
                        ['name' => $name],
                        [
                            'display_name' => $displayName,
                            'module' => $module,
                            'description' => $displayName,
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
                    'description' => 'Memiliki seluruh hak akses JANETA.',
                ],

                'admin' => [
                    'display_name' => 'Admin',
                    'description' => 'Mengelola operasional dan pengguna JANETA.',
                ],

                'operator' => [
                    'display_name' => 'Operator',
                    'description' => 'Mengelola operasional OLT dan ONT.',
                ],

                'customer-care' => [
                    'display_name' => 'Customer Care',
                    'description' => 'Mengakses informasi pelanggan dan ONT.',
                ],

                'viewer' => [
                    'display_name' => 'Viewer',
                    'description' => 'Hanya memiliki hak akses melihat data.',
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

                    'ont.lookup',
                    'ont.processing',
                    'ont.control',
                    'ont.restart',
                    'ont.activate',
                    'ont.deactivate',

                    'olt.activity.view',
                    'olt.view',
                    'olt.create',
                    'olt.update',
                    'olt.delete',
                    'olt.test',

                    'telegram.view',
                    'telegram.manage',

                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',

                    'audit.view',

                    'access.view',
                    'access.manage',
                ],

                // Operasional jaringan
                'operator' => [
                    'dashboard.view',

                    'ont.lookup',
                    'ont.processing',
                    'ont.control',
                    'ont.restart',

                    'olt.activity.view',
                    'olt.view',
                    'olt.test',
                ],

                // Customer Care
                'customer-care' => [
                    'dashboard.view',
                    'ont.lookup',
                    'ont.processing',
                ],

                // Read only
                'viewer' => [
                    'dashboard.view',
                    'ont.lookup',
                    'ont.processing',
                    'olt.view',
                    'olt.activity.view',
                    'telegram.view',
                ],
            ];

            foreach ($rolePermissions as $roleName => $permissions) {

                $ids = collect($permissions)
                    ->map(fn ($permission) => $permissionIds[$permission])
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

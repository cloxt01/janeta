<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WhitelistEntry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('JANETA_SUPER_ADMIN_EMAIL', 'admin@jangkaunet.com')],
            [
                'name' => env('DASHBOARD_ADMIN_NAME', 'JANETA Administrator'),
                'password' => env('DASHBOARD_ADMIN_PASSWORD', '12345678'),
            ],
        );

        $this->call([
            AccessControlSeeder::class,
        ]);

        WhitelistEntry::upsert([
            ['chat_id' => '7444614295',     'name' => 'Ferdi IS',               'role' => 'admin'],
            ['chat_id' => '1169472938',     'name' => 'Ikhsan - Customer Care', 'role' => 'user'],
            ['chat_id' => '-1004398438467', 'name' => 'Group NOC',              'role' => 'user'],
        ], ['chat_id'], ['name', 'role']);
    }
}

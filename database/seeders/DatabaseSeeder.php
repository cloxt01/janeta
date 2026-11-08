<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => env('DASHBOARD_ADMIN_EMAIL', 'admin@jangkaunet.com')],
            [
                'name' => env('DASHBOARD_ADMIN_NAME', 'JANETA Administrator'),
                'password' => env('DASHBOARD_ADMIN_PASSWORD', '12345678'),
                'is_admin' => true,
            ],
        );

        \App\Models\WhitelistEntry::upsert([
            ['chat_id' => '7444614295', 'name' => 'Ferdi IS', 'is_admin' => true],
            ['chat_id' => '1169472938', 'name' => 'Ikhsan - Customer Care', 'is_admin' => false],
            ['chat_id' => '-1004398438467', 'name' => 'Group NOC', 'is_admin' => false],
        ], ['chat_id'], ['name', 'is_admin']);

    }
}

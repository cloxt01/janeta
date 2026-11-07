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

        $merek = collect(['Huawei', 'TP-Link', 'CData'])->mapWithKeys(
            fn (string $nama) => [$nama => \App\Models\Merek::firstOrCreate(['nama' => $nama])],
        );

        $mappings = [
            ['equipment_id' => 'HG8145V5', 'brand' => 'Huawei', 'wlan_band' => 'Dual Band 2.4G/5G'],
            ['equipment_id' => 'EG8245H5', 'brand' => 'Huawei', 'wlan_band' => 'Single Band 2.4G'],
            ['equipment_id' => 'EG8141H5', 'brand' => 'Huawei', 'wlan_band' => 'Single Band 2.4G'],
            ['equipment_id' => 'XC220-G3', 'brand' => 'TP-Link', 'wlan_band' => 'Dual Band 2.4G/5G'],
            ['equipment_id' => 'FD511GD', 'brand' => 'CData', 'wlan_band' => 'Dual Band 2.4G/5G'],
        ];

        foreach ($mappings as $mapping) {
            $tipe = \App\Models\Tipe::firstOrCreate([
                'merek_id' => $merek[$mapping['brand']]->id,
                'nama' => $mapping['equipment_id'],
            ]);

            \App\Models\OntTypeMapping::updateOrCreate(
                ['equipment_id' => $mapping['equipment_id']],
                [
                    'brand' => $mapping['brand'],
                    'merek_id' => $tipe->merek_id,
                    'tipe_id' => $tipe->id,
                    'wlan_band' => $mapping['wlan_band'],
                ],
            );
        }
    }
}

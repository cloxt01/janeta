<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->boolean('ssh_enabled')->default(true)->after('transport_type');
            $table->unsignedSmallInteger('ssh_port')->default(22)->after('ssh_enabled');
            $table->string('ssh_username')->nullable()->after('ssh_port');
            $table->text('ssh_password')->nullable()->after('ssh_username');
            $table->text('ssh_private_key')->nullable()->after('ssh_password');
            $table->boolean('snmp_enabled')->default(false)->after('ssh_private_key');
            $table->unsignedSmallInteger('snmp_port')->default(161)->after('snmp_enabled');
            $table->string('snmp_community')->nullable()->after('snmp_port');
        });

        DB::table('olts')->orderBy('id')->each(function (object $olt): void {
            $password = $olt->password;
            try {
                $password = Crypt::decryptString($password);
            } catch (DecryptException) {
                // Keep legacy plaintext credentials unchanged.
            }

            DB::table('olts')->where('id', $olt->id)->update([
                'ssh_enabled' => $olt->connection_type === 'ssh',
                'ssh_port' => $olt->connection_type === 'ssh' ? $olt->port : 22,
                'ssh_username' => $olt->connection_type === 'ssh' ? $olt->username : null,
                'ssh_password' => $olt->connection_type === 'ssh' ? $password : null,
                'snmp_enabled' => $olt->connection_type === 'snmp',
                'snmp_port' => $olt->connection_type === 'snmp' ? $olt->port : 161,
                'snmp_community' => $olt->connection_type === 'snmp' ? $olt->username : null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_enabled', 'ssh_port', 'ssh_username', 'ssh_password', 'ssh_private_key',
                'snmp_enabled', 'snmp_port', 'snmp_community',
            ]);
        });
    }
};

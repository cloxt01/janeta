<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            if (! Schema::hasColumn('mikrotik_routers', 'api_enabled')) {
                $table->boolean('api_enabled')->default(true)->after('password');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'api_port')) {
                $table->unsignedSmallInteger('api_port')->default(8728)->after('api_enabled');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'api_ssl')) {
                $table->boolean('api_ssl')->default(false)->after('api_port');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'api_username')) {
                $table->string('api_username')->nullable()->after('api_ssl');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'api_password')) {
                $table->text('api_password')->nullable()->after('api_username');
            }

            if (! Schema::hasColumn('mikrotik_routers', 'ssh_enabled')) {
                $table->boolean('ssh_enabled')->default(false)->after('api_password');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'ssh_port')) {
                $table->unsignedSmallInteger('ssh_port')->default(22)->after('ssh_enabled');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'ssh_username')) {
                $table->string('ssh_username')->nullable()->after('ssh_port');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'ssh_password')) {
                $table->text('ssh_password')->nullable()->after('ssh_username');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'ssh_private_key')) {
                $table->text('ssh_private_key')->nullable()->after('ssh_password');
            }

            if (! Schema::hasColumn('mikrotik_routers', 'snmp_enabled')) {
                $table->boolean('snmp_enabled')->default(false)->after('ssh_private_key');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'snmp_port')) {
                $table->unsignedSmallInteger('snmp_port')->default(161)->after('snmp_enabled');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'snmp_community')) {
                $table->string('snmp_community')->nullable()->after('snmp_port');
            }
        });

        DB::table('mikrotik_routers')->orderBy('id')->each(function (object $router): void {
            $password = $router->password;
            try {
                $password = Crypt::decryptString($password);
            } catch (DecryptException) {
                // Pertahankan kredensial lama jika masih plaintext.
            }

            $encryptedPassword = Crypt::encryptString((string) $password);

            DB::table('mikrotik_routers')->where('id', $router->id)->update([
                'api_enabled' => true,
                'api_port' => (int) ($router->port ?: 8728),
                'api_ssl' => ((int) $router->port) === 8729,
                'api_username' => $router->username,
                'api_password' => $encryptedPassword,
                'ssh_username' => $router->username,
                'ssh_password' => $encryptedPassword,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->dropColumn([
                'api_enabled',
                'api_port',
                'api_ssl',
                'api_username',
                'api_password',
                'ssh_enabled',
                'ssh_port',
                'ssh_username',
                'ssh_password',
                'ssh_private_key',
                'snmp_enabled',
                'snmp_port',
                'snmp_community',
            ]);
        });
    }
};

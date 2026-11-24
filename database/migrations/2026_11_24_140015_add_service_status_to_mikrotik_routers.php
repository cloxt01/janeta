<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            if (! Schema::hasColumn('mikrotik_routers', 'api_status')) {
                $table->string('api_status')->default('unknown')->after('api_password');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'api_last_checked_at')) {
                $table->timestamp('api_last_checked_at')->nullable()->after('api_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'api_last_error')) {
                $table->text('api_last_error')->nullable()->after('api_last_checked_at');
            }

            if (! Schema::hasColumn('mikrotik_routers', 'ssh_status')) {
                $table->string('ssh_status')->default('unknown')->after('ssh_private_key');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'ssh_last_checked_at')) {
                $table->timestamp('ssh_last_checked_at')->nullable()->after('ssh_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'ssh_last_error')) {
                $table->text('ssh_last_error')->nullable()->after('ssh_last_checked_at');
            }

            if (! Schema::hasColumn('mikrotik_routers', 'snmp_status')) {
                $table->string('snmp_status')->default('unknown')->after('snmp_community');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'snmp_last_checked_at')) {
                $table->timestamp('snmp_last_checked_at')->nullable()->after('snmp_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'snmp_last_error')) {
                $table->text('snmp_last_error')->nullable()->after('snmp_last_checked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->dropColumn([
                'api_status', 'api_last_checked_at', 'api_last_error',
                'ssh_status', 'ssh_last_checked_at', 'ssh_last_error',
                'snmp_status', 'snmp_last_checked_at', 'snmp_last_error',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->string('ssh_status')->default('unknown')->after('ssh_private_key');
            $table->timestamp('ssh_last_checked_at')->nullable()->after('ssh_status');
            $table->text('ssh_last_error')->nullable()->after('ssh_last_checked_at');
            $table->string('snmp_status')->default('unknown')->after('snmp_community');
            $table->timestamp('snmp_last_checked_at')->nullable()->after('snmp_status');
            $table->text('snmp_last_error')->nullable()->after('snmp_last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_status', 'ssh_last_checked_at', 'ssh_last_error',
                'snmp_status', 'snmp_last_checked_at', 'snmp_last_error',
            ]);
        });
    }
};

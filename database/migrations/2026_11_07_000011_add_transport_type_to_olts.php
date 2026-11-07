<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->string('transport_type')->default('tcp')->after('connection_type');
        });

        DB::table('olts')
            ->where('connection_type', 'snmp')
            ->update(['transport_type' => 'udp']);
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn('transport_type');
        });
    }
};

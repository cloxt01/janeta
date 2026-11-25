<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_activity_logs', function (Blueprint $table) {
            $table->date('router_date')
                ->nullable()
                ->after('topics');

            $table->string('router_timezone')
                ->nullable()
                ->after('router_time');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_activity_logs', function (Blueprint $table) {
            $table->dropColumn([
                'router_date',
                'router_timezone',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('hris_role')->default('operator')->after('role');
        });

        \DB::table('users')->whereNull('hris_role')->update(['hris_role' => 'operator']);
        \DB::table('users')->where('hris_role', 'operator')->update(['hris_role' => \DB::raw('role')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('hris_role');
        });
    }
};

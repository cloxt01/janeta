<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_merek', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('tbl_tipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merek_id')->constrained('tbl_merek')->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
            $table->unique(['merek_id', 'nama']);
        });

        Schema::table('ont_type_mappings', function (Blueprint $table) {
            $table->foreignId('merek_id')->nullable()->after('brand')->constrained('tbl_merek')->nullOnDelete();
            $table->foreignId('tipe_id')->nullable()->after('merek_id')->constrained('tbl_tipe')->nullOnDelete();
        });

        $merekIds = [];
        DB::table('ont_type_mappings')->select('brand')->distinct()->get()->each(function ($mapping) use (&$merekIds) {
            $merekIds[$mapping->brand] = DB::table('tbl_merek')->insertGetId([
                'nama' => $mapping->brand,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('ont_type_mappings')->get()->each(function ($mapping) use ($merekIds) {
            $tipeId = DB::table('tbl_tipe')->insertGetId([
                'merek_id' => $merekIds[$mapping->brand],
                'nama' => $mapping->equipment_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ont_type_mappings')->where('id', $mapping->id)->update([
                'merek_id' => $merekIds[$mapping->brand],
                'tipe_id' => $tipeId,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ont_type_mappings', function (Blueprint $table) {
            $table->dropForeign(['tipe_id']);
            $table->dropForeign(['merek_id']);
            $table->dropColumn(['tipe_id', 'merek_id']);
        });

        Schema::dropIfExists('tbl_tipe');
        Schema::dropIfExists('tbl_merek');
    }
};

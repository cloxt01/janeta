<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('ont_type_mappings');

        if (Schema::hasTable('tbl_merek') && ! Schema::hasTable('brands')) {
            Schema::rename('tbl_merek', 'brands');
        }

        if (Schema::hasTable('tbl_tipe') && ! Schema::hasTable('ont_types')) {
            Schema::rename('tbl_tipe', 'ont_types');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ont_types') && ! Schema::hasTable('tbl_tipe')) {
            Schema::rename('ont_types', 'tbl_tipe');
        }

        if (Schema::hasTable('brands') && ! Schema::hasTable('tbl_merek')) {
            Schema::rename('brands', 'tbl_merek');
        }

        if (! Schema::hasTable('ont_type_mappings')) {
            Schema::create('ont_type_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('equipment_id')->unique();
                $table->string('brand');
                $table->string('wlan_band');
                $table->foreignId('merek_id')->nullable()->constrained('tbl_merek')->nullOnDelete();
                $table->foreignId('tipe_id')->nullable()->constrained('tbl_tipe')->nullOnDelete();
                $table->timestamps();
            });
        }
    }
};

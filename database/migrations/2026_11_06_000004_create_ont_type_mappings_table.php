<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ont_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_id')->unique();
            $table->string('brand');
            $table->string('wlan_band');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ont_type_mappings');
    }
};

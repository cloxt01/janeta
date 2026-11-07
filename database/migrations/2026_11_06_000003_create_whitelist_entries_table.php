<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whitelist_entries', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 64)->unique();
            $table->string('name')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelist_entries');
    }
};

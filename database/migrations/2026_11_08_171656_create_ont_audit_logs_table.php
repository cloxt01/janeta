<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ont_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->string('user_name')->nullable();
            $table->string('action');            // deactivate | activate | restart
            $table->string('olt');
            $table->string('frame');
            $table->string('slot');
            $table->string('port');
            $table->string('ont_id');
            $table->boolean('ok')->default(false);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ont_audit_logs');
    }
};

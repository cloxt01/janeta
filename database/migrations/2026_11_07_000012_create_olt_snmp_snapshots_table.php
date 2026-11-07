<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_snmp_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->string('frame')->default('0');
            $table->json('data');
            $table->text('error')->nullable();
            $table->timestamp('collected_at');
            $table->timestamps();
            $table->index(['olt_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_snmp_snapshots');
    }
};

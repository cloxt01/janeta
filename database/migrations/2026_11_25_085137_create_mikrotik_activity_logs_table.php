<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mikrotik_router_id')
                ->constrained('mikrotik_routers')
                ->cascadeOnDelete();

            $table->string('event_type', 40)->index();
            $table->string('topics')->nullable()->index();

            $table->string('router_time')->nullable();
            $table->string('router_user')->nullable()->index();

            $table->string('action')->nullable();
            $table->text('message')->nullable();

            $table->string('fingerprint', 64)->unique();

            $table->json('raw_data')->nullable();

            $table->timestamp('collected_at')->useCurrent();
            $table->timestamps();

            $table->index([
                'mikrotik_router_id',
                'event_type',
                'collected_at',
            ], 'mikrotik_activity_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_activity_logs');
    }
};

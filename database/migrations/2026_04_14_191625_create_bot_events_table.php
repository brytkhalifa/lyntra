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
        Schema::create('bot_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint', 32);
            $table->string('action', 64)->nullable();
            $table->string('reason', 64);
            $table->string('ip_hash')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->text('referrer')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['short_link_id', 'created_at']);
            $table->index(['endpoint', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_events');
    }
};

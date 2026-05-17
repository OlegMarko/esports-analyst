<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_players', function (Blueprint $table) {
            $table->id();
            $table->string('faceit_id')->unique();
            $table->string('faceit_nickname');
            $table->string('steam_id')->nullable();
            $table->string('avatar')->nullable();
            $table->integer('faceit_level')->default(1);
            $table->integer('elo')->default(1000);
            $table->boolean('active')->default(true);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_players');
    }
};

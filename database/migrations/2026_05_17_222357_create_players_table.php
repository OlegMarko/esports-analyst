<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('faceit_player_id');
            $table->string('faceit_nickname');
            $table->string('team');
            $table->string('result');
            $table->float('kda')->default(0);
            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('damage_dealt')->default(0);
            $table->float('hs_percent')->default(0);
            $table->integer('headshots')->default(0);
            $table->integer('utility_damage')->default(0);
            $table->integer('clutches_won')->default(0);
            $table->integer('mvp_count')->default(0);
            $table->float('adr')->default(0);
            $table->float('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};

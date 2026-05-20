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
        Schema::create('live_matches', function (Blueprint $table) {
            $table->id();
            $table->string('faceit_match_id')->unique();
            $table->string('game')->default('cs2');
            $table->string('status');
            $table->string('map')->nullable();
            $table->string('team_a_name');
            $table->string('team_b_name');
            $table->json('team_a_roster');
            $table->json('team_b_roster');
            $table->unsignedSmallInteger('team_a_elo_avg')->nullable();
            $table->unsignedSmallInteger('team_b_elo_avg')->nullable();
            $table->float('team_a_win_prob')->nullable();
            $table->float('team_b_win_prob')->nullable();
            $table->float('confidence')->nullable();
            $table->unsignedTinyInteger('margin_of_error')->nullable();
            $table->string('prediction_basis')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_matches');
    }
};

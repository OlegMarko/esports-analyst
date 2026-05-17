<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('faceit_match_id')->unique();
            $table->string('game')->default('cs2');
            $table->string('map')->nullable();
            $table->string('team_a_name')->nullable();
            $table->string('team_b_name')->nullable();
            $table->integer('team_a_score')->default(0);
            $table->integer('team_b_score')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamp('played_at')->nullable();
            $table->json('playstyle_tags')->nullable();
            $table->integer('eco_round_count')->default(0);
            $table->string('first_half_score')->nullable();
            $table->string('second_half_score')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamp('summary_at')->nullable();
            $table->json('raw_faceit_payload')->nullable();
            $table->timestamps();

            $table->index('faceit_match_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};

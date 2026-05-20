<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->json('key_moments')->nullable()->after('ai_summary');
            $table->string('mvp')->nullable()->after('key_moments');
            $table->unsignedTinyInteger('economy_rating')->nullable()->after('mvp');
            $table->unsignedTinyInteger('mechanical_rating')->nullable()->after('economy_rating');
            $table->unsignedTinyInteger('match_score')->nullable()->after('mechanical_rating');
            $table->json('similar_match_ids')->nullable()->after('match_score');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['key_moments', 'mvp', 'economy_rating', 'mechanical_rating', 'match_score', 'similar_match_ids']);
        });
    }
};

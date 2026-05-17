<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('match_aspect_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('game');
            $table->string('aspect');
            $table->float('aspect_score')->default(0.5);
            $table->timestamps();
        });

        Schema::table('match_aspect_embeddings', function (Blueprint $table) {
            $table->vector('embedding', 768)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_aspect_embeddings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('headline')->nullable()->after('ai_summary');
        });

        Schema::table('tracked_players', function (Blueprint $table) {
            $table->text('performance_brief')->nullable()->after('last_polled_at');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('headline');
        });

        Schema::table('tracked_players', function (Blueprint $table) {
            $table->dropColumn('performance_brief');
        });
    }
};

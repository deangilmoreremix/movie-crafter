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
        Schema::table('movie_story_boards', function (Blueprint $table) {
            $table->index('movie_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_story_boards', function (Blueprint $table) {
            $table->dropIndex(['movie_id']);
            $table->dropIndex(['order']);
        });
    }
};
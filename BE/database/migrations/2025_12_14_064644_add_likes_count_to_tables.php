<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0);
        });
        Schema::table('albums', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0);
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};

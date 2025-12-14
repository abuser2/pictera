<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedBigInteger('global_id')->nullable()->unique();
        });
        Schema::table('albums', function (Blueprint $table) {
            $table->unsignedBigInteger('global_id')->nullable()->unique();
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('global_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('global_id');
        });
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('global_id');
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('global_id');
        });
    }
};

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('visibility', ['public','private','unlisted'])->default('private');
            $table->timestamps();
        });

        Schema::create('album_photo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->unique(['album_id','photo_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('album_photo');
        Schema::dropIfExists('albums');
    }
};

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('visibility', ['public','private','unlisted'])->default('private');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('photos');
    }
};

<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // автор поста (профиль)
            $table->text('caption')->nullable();                             // подпись
            $table->enum('visibility', ['public','private','unlisted'])->default('public');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('post_photo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable();                // порядок в посте
            $table->unique(['post_id','photo_id']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('post_photo');
        Schema::dropIfExists('posts');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Создает поля likeable_id и likeable_type для связи с фото, альбомами и постами
            $table->morphs('likeable'); 
            $table->timestamps();

            // Уникальный индекс, чтобы пользователь мог лайкнуть объект только один раз
            $table->unique(['user_id', 'likeable_id', 'likeable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};

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
        Schema::table('albums', function (Blueprint $table) {
            // Добавляем поле для ID обложки.
            // onDelete('set null') означает, что если фото-обложка будет удалена,
            // у альбома просто не будет обложки, но сам альбом не удалится.
            $table->foreignId('cover_photo_id')->nullable()->after('user_id')
                  ->constrained('photos')->onDelete('set null');
        });
    }
    
    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            // Удаляем внешний ключ и колонку
            $table->dropForeign(['cover_photo_id']);
            $table->dropColumn('cover_photo_id');
        });
    }

};

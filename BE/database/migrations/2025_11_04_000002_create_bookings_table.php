<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // кого бронируют (фотограф) и кто бронирует (клиент)
            $table->foreignId('photographer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            // интервал времени
            $table->dateTime('start_at');
            $table->dateTime('end_at');

            // можно связать с альбомом после съёмки
            $table->foreignId('album_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', ['pending','confirmed','cancelled','completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // индексы
            $table->index(['photographer_id','start_at']);
            $table->unique(['photographer_id','start_at','end_at']); // защита от точного дубля слота
        });

        // (опционально) гарантия валидности интервала
        // MySQL 8/SQLite поддержат CHECK, на старом MySQL можно пропустить.
        try {
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT chk_time CHECK (start_at < end_at)');
        } catch (\Throwable $e) { /* no-op for unsupported DBs */ }
    }

    public function down(): void {
        Schema::dropIfExists('bookings');
    }
};
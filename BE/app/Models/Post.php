<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    // Разрешённые поля для массового заполнения
    protected $fillable = [
        'user_id',        // автор поста
        'caption',        // подпись
        'visibility',     // 'public'|'private'|'unlisted'
        'published_at',   // опционально
    ];

    // Касты
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /** Автор поста */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Фотографии поста (pivot: post_photo).
     * Если таблица называется НЕ photo_post, укажем имя явно.
     * withPivot('position') — если нужно хранить порядок.
     * withTimestamps() — только если в pivot есть created_at/updated_at.
     */
    public function photos()
    {
        return $this->belongsToMany(Photo::class, 'post_photo')
                    ->withPivot('position')
                    ->withTimestamps();
    }

    /** Удобный аксессор: кол-во фото */
    public function getPhotosCountAttribute(): int
    {
        return $this->relationLoaded('photos') ? $this->photos->count() : 
               $this->photos()->count();
    }
}

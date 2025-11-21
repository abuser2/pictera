<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Photo extends Model
{
    protected $fillable = [
        'user_id','path','original_name','title','description','visibility'
    ];

    // добавим автоматическое поле в JSON
    protected $appends = ['url']; // +++

    // accessor: вернёт полный URL к файлу
    public function getUrlAttribute() // +++
    {
        // эквивалентно APP_URL.'/storage/'.$this->path для диска web
        //return Storage::disk(config('filesystems.default'))->url($this->path);
        return Storage::disk('web')->url($this->path);
    }

    public function posts() { return $this->belongsToMany(Post::class)->withPivot('position')->withTimestamps(); }
    
    public function user() { return $this->belongsTo(User::class); }

    public function albums() { return $this->belongsToMany(Album::class); }

    public function shares() { return $this->morphMany(Share::class, 'shareable'); }
}

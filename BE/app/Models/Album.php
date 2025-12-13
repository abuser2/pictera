<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $fillable = ['user_id','name','description','visibility'];

    public function user() { return $this->belongsTo(User::class); }

    public function photos() { return $this->belongsToMany(Photo::class); }

    public function shares() { return $this->morphMany(Share::class, 'shareable'); }
    
    public function coverPhoto() { return $this->belongsTo(Photo::class, 'cover_photo_id'); }
}

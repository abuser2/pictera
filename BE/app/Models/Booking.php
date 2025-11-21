<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'photographer_id','client_id','start_at','end_at','album_id','status','notes'
    ];
    protected $casts = ['start_at'=>'datetime', 'end_at'=>'datetime'];

    public function photographer() { return $this->belongsTo(User::class, 'photographer_id'); }
    public function client()       { return $this->belongsTo(User::class, 'client_id'); }
    public function album()        { return $this->belongsTo(Album::class); }
}
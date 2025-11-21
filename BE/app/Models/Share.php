<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    protected $fillable = ['user_id','token','expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function shareable() { return $this->morphTo(); }

    public function isValid(): bool {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}

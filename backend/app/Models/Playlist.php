<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'orientation',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('display_order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'url',
        'filename',
        'mime_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function playlistItems()
    {
        return $this->hasMany(PlaylistItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistItem extends Model
{
    protected $fillable = [
        'playlist_id',
        'asset_id',
        'display_order',
        'duration_seconds',
        'transition_effect',
    ];

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}

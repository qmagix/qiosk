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
        'crop_data',
    ];

    protected $casts = [
        'crop_data' => 'array',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingUpload extends Model
{
    protected $fillable = [
        'playlist_id',
        'asset_id',
        'status',
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

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
        'visibility',
        'access_token',
        'allow_uploads',
        'upload_token',
        'upload_mode',
        'qr_frequency',
    ];

    protected $casts = [
        'allow_uploads' => 'boolean',
        'qr_frequency' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('display_order');
    }

    public function pendingUploads()
    {
        return $this->hasMany(PendingUpload::class)->where('status', 'pending');
    }
}

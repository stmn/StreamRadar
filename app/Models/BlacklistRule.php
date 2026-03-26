<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistRule extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'value',
        'twitch_user_id',
        'profile_image_url',
    ];

    public function scopeChannels($query)
    {
        return $query->where('type', 'channel');
    }

    public function scopeKeywords($query)
    {
        return $query->where('type', 'keyword');
    }

    public function scopeTags($query)
    {
        return $query->where('type', 'tag');
    }
}

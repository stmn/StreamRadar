<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IgnoredStreamer extends Model
{
    protected $fillable = [
        'twitch_user_id',
        'user_login',
        'user_name',
        'profile_image_url',
        'reason',
    ];
}

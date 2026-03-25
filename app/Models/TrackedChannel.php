<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackedChannel extends Model
{
    protected $fillable = [
        'twitch_user_id',
        'user_login',
        'user_name',
        'profile_image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

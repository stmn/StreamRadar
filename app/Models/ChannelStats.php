<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelStats extends Model
{
    protected $fillable = ['user_login', 'avg_viewers', 'fetched_at'];

    protected function casts(): array
    {
        return [
            'avg_viewers' => 'integer',
            'fetched_at' => 'datetime',
        ];
    }
}

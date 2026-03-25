<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamAlertTracking extends Model
{
    public $timestamps = false;

    protected $table = 'stream_alert_tracking';

    protected $fillable = [
        'alert_rule_id',
        'stream_twitch_id',
        'streamer_login',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
        ];
    }

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }
}

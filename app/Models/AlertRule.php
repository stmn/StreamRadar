<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'streamer_login',
        'category_id',
        'match_mode',
        'min_viewers',
        'language',
        'keywords',
        'notify_email',
        'notify_discord',
        'notify_telegram',
        'notify_webhook',
        'notify_on_category_change',
        'notify_on_stream_start',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_viewers' => 'integer',
            'keywords' => 'array',
            'notify_email' => 'boolean',
            'notify_discord' => 'boolean',
            'notify_telegram' => 'boolean',
            'notify_webhook' => 'boolean',
            'notify_on_category_change' => 'boolean',
            'notify_on_stream_start' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(StreamAlertTracking::class);
    }

    public function latestTracking(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StreamAlertTracking::class)->whereNotNull('triggered_at')->latestOfMany('triggered_at');
    }

    public function isForSpecificStreamer(): bool
    {
        return $this->streamer_login !== null;
    }

    public function matchesStream(Stream $stream): bool
    {
        if ($this->streamer_login && strtolower($this->streamer_login) !== strtolower($stream->user_login)) {
            return false;
        }

        if ($this->category_id && $this->category_id !== $stream->category_id) {
            return false;
        }

        if ($this->min_viewers && $stream->viewer_count < $this->min_viewers) {
            return false;
        }

        if ($this->language && strtolower($this->language) !== strtolower($stream->language ?? '')) {
            return false;
        }

        if (! empty($this->keywords)) {
            $titleLower = strtolower($stream->title);
            $matched = false;
            foreach ($this->keywords as $keyword) {
                if (str_contains($titleLower, strtolower($keyword))) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return false;
            }
        }

        return true;
    }
}

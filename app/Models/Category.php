<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'twitch_id',
        'name',
        'box_art_url',
        'is_active',
        'notifications_enabled',
        'use_global_filters',
        'min_viewers',
        'languages',
        'keywords',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'notifications_enabled' => 'boolean',
            'use_global_filters' => 'boolean',
            'min_viewers' => 'integer',
            'languages' => 'array',
            'keywords' => 'array',
        ];
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }

    public function effectiveFilters(): array
    {
        if ($this->use_global_filters) {
            return [
                'min_viewers' => (int) Setting::get('global_min_viewers', 0) ?: null,
                'languages' => Setting::get('global_languages') ? json_decode(Setting::get('global_languages'), true) : [],
                'keywords' => Setting::get('global_keywords') ? json_decode(Setting::get('global_keywords'), true) : [],
            ];
        }

        return [
            'min_viewers' => $this->min_viewers,
            'languages' => $this->languages ?? [],
            'keywords' => $this->keywords ?? [],
        ];
    }

    public function getBoxArtUrlSized(int $width = 188, int $height = 250): ?string
    {
        if (! $this->box_art_url) {
            return null;
        }

        return str_replace(['{width}', '{height}'], [$width, $height], $this->box_art_url);
    }
}

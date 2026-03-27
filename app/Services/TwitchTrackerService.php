<?php

namespace App\Services;

use App\Models\ChannelStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitchTrackerService
{
    private const CACHE_HOURS = 24;

    /**
     * Get avg_viewers for a channel. Returns cached value if fresh, otherwise fetches from TwitchTracker.
     */
    public function getAvgViewers(string $userLogin): ?int
    {
        $stats = ChannelStats::where('user_login', strtolower($userLogin))->first();

        if ($stats && $stats->fetched_at && $stats->fetched_at->diffInHours(now()) < self::CACHE_HOURS) {
            return $stats->avg_viewers;
        }

        return $this->fetchAndCache(strtolower($userLogin), $stats);
    }

    /**
     * Bulk-fetch avg_viewers for multiple logins. Only fetches stale/missing entries.
     * Returns map of login => avg_viewers.
     */
    public function getAvgViewersBulk(array $userLogins): array
    {
        $logins = array_map('strtolower', $userLogins);
        $existing = ChannelStats::whereIn('user_login', $logins)->get()->keyBy('user_login');
        $result = [];

        foreach ($logins as $login) {
            $stats = $existing->get($login);
            if ($stats && $stats->fetched_at && $stats->fetched_at->diffInHours(now()) < self::CACHE_HOURS) {
                $result[$login] = $stats->avg_viewers;
            } else {
                $result[$login] = $this->fetchAndCache($login, $stats);
            }
        }

        return $result;
    }

    private function fetchAndCache(string $login, ?ChannelStats $stats): ?int
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'StreamRadar/1.0'])
                ->get("https://twitchtracker.com/api/channels/summary/{$login}");

            if (! $response->successful()) {
                return $stats?->avg_viewers;
            }

            $data = $response->json();
            $avgViewers = $data['avg_viewers'] ?? null;

            if ($avgViewers !== null) {
                $avgViewers = (int) $avgViewers;
            }

            ChannelStats::updateOrCreate(
                ['user_login' => $login],
                ['avg_viewers' => $avgViewers, 'fetched_at' => now()],
            );

            return $avgViewers;
        } catch (\Exception $e) {
            Log::debug("TwitchTracker fetch failed for {$login}: {$e->getMessage()}");

            return $stats?->avg_viewers;
        }
    }
}

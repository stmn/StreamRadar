<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitchApiService
{
    private const TOKEN_CACHE_KEY = 'twitch_access_token';

    private const BASE_URL = 'https://api.twitch.tv/helix';

    private const TOKEN_URL = 'https://id.twitch.tv/oauth2/token';

    public function getClientId(): ?string
    {
        return Setting::get('twitch_client_id', config('services.twitch.client_id'));
    }

    private function getClientSecret(): ?string
    {
        return Setting::get('twitch_client_secret', config('services.twitch.client_secret'));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->getClientId()) && ! empty($this->getClientSecret());
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Twitch API credentials not configured'];
        }

        try {
            $token = $this->fetchAccessToken();

            return ['success' => true, 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3600, fn () => $this->fetchAccessToken());
    }

    private function fetchAccessToken(): string
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to obtain Twitch access token: '.$response->body());
        }

        $data = $response->json();
        $expiresIn = max(($data['expires_in'] ?? 3600) - 60, 60);
        Cache::put(self::TOKEN_CACHE_KEY, $data['access_token'], $expiresIn);

        return $data['access_token'];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders([
                'Client-ID' => $this->getClientId(),
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])
            ->throw();
    }

    private function request(string $method, string $url, array $query = []): array
    {
        try {
            $response = $this->client()->{$method}($url, $query);

            return $response->json();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response?->status() === 401) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $response = $this->client()->{$method}($url, $query);

                return $response->json();
            }
            throw $e;
        }
    }

    public function searchCategories(string $query, int $limit = 20): array
    {
        $response = $this->request('get', '/search/categories', [
            'query' => $query,
            'first' => $limit,
        ]);

        return $response['data'] ?? [];
    }

    public function getCategories(array $twitchIds): array
    {
        if (empty($twitchIds)) {
            return [];
        }

        $response = $this->request('get', '/games', [
            'id' => $twitchIds,
        ]);

        return $response['data'] ?? [];
    }

    public function getStreamsByCategory(string $gameId, ?string $cursor = null, int $first = 100): array
    {
        $params = [
            'game_id' => $gameId,
            'first' => $first,
        ];

        if ($cursor) {
            $params['after'] = $cursor;
        }

        return $this->request('get', '/streams', $params);
    }

    /**
     * Fetch all streams for a category (handles pagination).
     *
     * @return array<array> All stream data
     */
    public function getAllStreamsForCategory(string $gameId, int $maxPages = 5): array
    {
        $allStreams = [];
        $cursor = null;

        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->getStreamsByCategory($gameId, $cursor);
            $streams = $response['data'] ?? [];

            if (empty($streams)) {
                break;
            }

            $allStreams = array_merge($allStreams, $streams);
            $cursor = $response['pagination']['cursor'] ?? null;

            if (! $cursor) {
                break;
            }
        }

        return $allStreams;
    }

    /**
     * Get live streams for specific user logins.
     */
    public function getStreamsByUsers(array $logins): array
    {
        if (empty($logins)) {
            return [];
        }

        $allStreams = [];
        $chunks = array_chunk($logins, 100);

        foreach ($chunks as $chunk) {
            $response = $this->request('get', '/streams', [
                'user_login' => $chunk,
                'first' => 100,
            ]);
            $allStreams = array_merge($allStreams, $response['data'] ?? []);
        }

        return $allStreams;
    }

    public function getUsers(array $logins): array
    {
        if (empty($logins)) {
            return [];
        }

        $chunks = array_chunk($logins, 100);
        $allUsers = [];

        foreach ($chunks as $chunk) {
            $response = $this->request('get', '/users', [
                'login' => $chunk,
            ]);

            $allUsers = array_merge($allUsers, $response['data'] ?? []);
        }

        return $allUsers;
    }

    public function getUsersByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $chunks = array_chunk($ids, 100);
        $allUsers = [];

        foreach ($chunks as $chunk) {
            $response = $this->request('get', '/users', [
                'id' => $chunk,
            ]);

            $allUsers = array_merge($allUsers, $response['data'] ?? []);
        }

        return $allUsers;
    }
}

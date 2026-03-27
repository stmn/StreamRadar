<?php

use App\Models\ChannelStats;
use App\Services\TwitchTrackerService;
use Illuminate\Support\Facades\Http;

test('fetches avg_viewers from TwitchTracker API', function () {
    Http::fake([
        'twitchtracker.com/*' => Http::response(['avg_viewers' => 1500], 200),
    ]);

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('teststreamer');

    expect($avg)->toBe(1500);
    $this->assertDatabaseHas('channel_stats', ['user_login' => 'teststreamer', 'avg_viewers' => 1500]);
});

test('returns cached value when fresh', function () {
    ChannelStats::create(['user_login' => 'cached', 'avg_viewers' => 300, 'fetched_at' => now()]);

    Http::fake(); // Should NOT be called

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('cached');

    expect($avg)->toBe(300);
    Http::assertNothingSent();
});

test('refetches when cache is stale (older than 24h)', function () {
    ChannelStats::create(['user_login' => 'stale', 'avg_viewers' => 100, 'fetched_at' => now()->subHours(25)]);

    Http::fake([
        'twitchtracker.com/*' => Http::response(['avg_viewers' => 200], 200),
    ]);

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('stale');

    expect($avg)->toBe(200);
});

test('returns cached value on HTTP error (404/500)', function () {
    ChannelStats::create(['user_login' => 'error', 'avg_viewers' => 50, 'fetched_at' => now()->subHours(25)]);

    Http::fake([
        'twitchtracker.com/*' => Http::response(null, 404),
    ]);

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('error');

    // Falls back to cached value
    expect($avg)->toBe(50);
});

test('returns null when no cache and API fails', function () {
    Http::fake([
        'twitchtracker.com/*' => Http::response(null, 500),
    ]);

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('unknown');

    expect($avg)->toBeNull();
});

test('returns null when API response has no avg_viewers key', function () {
    Http::fake([
        'twitchtracker.com/*' => Http::response(['some_other_field' => 123], 200),
    ]);

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('nodata');

    expect($avg)->toBeNull();
    $this->assertDatabaseHas('channel_stats', ['user_login' => 'nodata', 'avg_viewers' => null]);
});

test('returns cached value on connection exception', function () {
    ChannelStats::create(['user_login' => 'timeout', 'avg_viewers' => 75, 'fetched_at' => now()->subHours(25)]);

    Http::fake([
        'twitchtracker.com/*' => fn () => throw new \Exception('Connection timeout'),
    ]);

    $service = new TwitchTrackerService;
    $avg = $service->getAvgViewers('timeout');

    expect($avg)->toBe(75);
});

test('getAvgViewersBulk fetches only stale entries', function () {
    ChannelStats::create(['user_login' => 'fresh', 'avg_viewers' => 100, 'fetched_at' => now()]);
    // 'stale' has no entry

    Http::fake([
        'twitchtracker.com/api/channels/summary/stale' => Http::response(['avg_viewers' => 50], 200),
    ]);

    $service = new TwitchTrackerService;
    $result = $service->getAvgViewersBulk(['fresh', 'stale']);

    expect($result['fresh'])->toBe(100)
        ->and($result['stale'])->toBe(50);

    // Only 1 HTTP call (for 'stale', not 'fresh')
    Http::assertSentCount(1);
});

test('normalizes login to lowercase', function () {
    Http::fake([
        'twitchtracker.com/*' => Http::response(['avg_viewers' => 999], 200),
    ]);

    $service = new TwitchTrackerService;
    $service->getAvgViewers('MixedCase');

    $this->assertDatabaseHas('channel_stats', ['user_login' => 'mixedcase']);
});

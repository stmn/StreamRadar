<?php

use App\Models\Setting;
use App\Services\SyncService;
use App\Services\TwitchApiService;

test('POST /sync triggers sync when Twitch configured', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getAllStreamsForCategory')->andReturn([]);
        $mock->shouldReceive('getStreamsByUsers')->andReturn([]);
        $mock->shouldReceive('getUsersByIds')->andReturn([]);
    });

    $this->post('/sync')->assertRedirect();
});

test('POST /sync returns error when Twitch not configured', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->from('/')
        ->post('/sync')
        ->assertRedirect('/')
        ->assertSessionHas('error');
});

test('GET /sync/status returns last sync info', function () {
    Setting::set('last_sync_at', '2026-03-26T12:00:00+00:00');
    Setting::set('sync_frequency_minutes', '10');

    $this->getJson('/sync/status')
        ->assertOk()
        ->assertJson([
            'last_sync_at' => '2026-03-26T12:00:00+00:00',
            'sync_frequency_minutes' => '10',
        ]);
});

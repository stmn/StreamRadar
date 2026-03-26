<?php

use App\Models\BlacklistRule;
use App\Models\Category;
use App\Models\Setting;
use App\Models\TrackedChannel;
use App\Services\TwitchApiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

test('GET /settings returns 200 with settings', function () {
    $this->get('/settings')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Settings')
            ->has('settings')
        );
});

test('GET /settings masks twitch_client_secret', function () {
    Setting::set('twitch_client_secret', 'my_super_secret_key_123');

    $this->get('/settings')
        ->assertInertia(fn ($page) => $page
            ->where('settings.twitch_client_secret_masked', fn ($val) => str_starts_with($val, '***'))
        );
});

test('PUT /settings saves settings', function () {
    $this->put('/settings', ['theme' => 'dark'])->assertRedirect();

    expect(Setting::get('theme'))->toBe('dark');
});

test('PUT /settings does not overwrite secret with masked value', function () {
    Setting::set('twitch_client_secret', 'real_secret');

    $this->put('/settings', ['twitch_client_secret' => '********cret']);

    expect(Setting::get('twitch_client_secret'))->toBe('real_secret');
});

test('PUT /settings hashes auth_password', function () {
    $this->put('/settings', [
        'auth_username' => 'admin',
        'auth_password' => 'mypassword',
    ])->assertRedirect();

    $hashed = Setting::get('auth_password');
    expect(password_verify('mypassword', $hashed))->toBeTrue();
});

test('POST /settings/disable-auth removes credentials with valid password', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('secret'));

    $this->withSession(['authenticated' => true])
        ->postJson('/settings/disable-auth', ['password' => 'secret'])
        ->assertOk();

    expect(Setting::get('auth_username'))->toBeNull()
        ->and(Setting::get('auth_password'))->toBeNull();
});

test('POST /settings/disable-auth rejects invalid password', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('secret'));

    $this->withSession(['authenticated' => true])
        ->postJson('/settings/disable-auth', ['password' => 'wrong'])
        ->assertStatus(422);
});

test('GET /settings/export returns JSON with data', function () {
    Category::factory()->create();
    TrackedChannel::factory()->create();
    BlacklistRule::factory()->create();
    Setting::set('theme', 'dark');

    $response = $this->get('/settings/export')->assertOk();
    $data = $response->json();

    expect($data)->toHaveKeys(['settings', 'categories', 'channels', 'blacklist']);
});

test('POST /settings/import imports from JSON file', function () {
    $exportData = [
        'settings' => ['theme' => 'light'],
        'categories' => [['twitch_id' => '123', 'name' => 'Test', 'is_active' => true, 'notifications_enabled' => true, 'use_global_filters' => true]],
        'channels' => [],
        'blacklist' => [],
    ];

    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($exportData));

    $this->post('/settings/import', ['file' => $file])->assertRedirect();

    expect(Setting::get('theme'))->toBe('light');
    $this->assertDatabaseHas('categories', ['twitch_id' => '123']);
});

test('POST /settings/import rejects invalid JSON', function () {
    $file = UploadedFile::fake()->createWithContent('bad.json', 'not json');

    $this->post('/settings/import', ['file' => $file])->assertRedirect();
});

test('POST /settings/test-twitch calls testConnection', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('testConnection')->once()->andReturn(['success' => true]);
    });

    $this->postJson('/settings/test-twitch')->assertOk();
});

test('POST /settings/test-discord sends webhook', function () {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    Setting::set('discord_webhook_url', 'https://discord.com/api/webhooks/test');

    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->postJson('/settings/test-discord')->assertOk();
});

test('POST /settings/test-email sends test email', function () {
    Mail::fake();
    Setting::set('mail_to', 'test@example.com');

    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->postJson('/settings/test-email')->assertOk();
    Mail::assertSentCount(1);
});

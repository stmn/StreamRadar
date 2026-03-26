<?php

use App\Models\BlacklistRule;
use App\Models\Stream;
use App\Services\TwitchApiService;

test('GET /blacklist returns 200 and lists rules by type', function () {
    BlacklistRule::factory()->channel()->count(2)->create();
    BlacklistRule::factory()->keyword()->create();

    $this->get('/blacklist')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Blacklist')
            ->has('channels', 2)
            ->has('keywords', 1)
        );
});

test('POST /blacklist creates a channel rule lowercased', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->post('/blacklist', [
        'type' => 'channel',
        'value' => 'MyChannel',
    ])->assertRedirect();

    $this->assertDatabaseHas('blacklist_rules', ['type' => 'channel', 'value' => 'mychannel']);
});

test('POST /blacklist creates a keyword rule', function () {
    $this->post('/blacklist', [
        'type' => 'keyword',
        'value' => 'gambling',
    ])->assertRedirect();

    $this->assertDatabaseHas('blacklist_rules', ['type' => 'keyword', 'value' => 'gambling']);
});

test('POST /blacklist creates a tag rule', function () {
    $this->post('/blacklist', [
        'type' => 'tag',
        'value' => 'NSFW',
    ])->assertRedirect();

    $this->assertDatabaseHas('blacklist_rules', ['type' => 'tag', 'value' => 'NSFW']);
});

test('POST /blacklist deletes matching streams for channel', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    Stream::factory()->create(['user_login' => 'baduser']);

    $this->post('/blacklist', ['type' => 'channel', 'value' => 'baduser']);

    $this->assertDatabaseMissing('streams', ['user_login' => 'baduser']);
});

test('POST /blacklist deletes matching streams for keyword in title', function () {
    Stream::factory()->create(['title' => 'Come watch gambling streams']);
    Stream::factory()->create(['title' => 'Normal stream']);

    $this->post('/blacklist', ['type' => 'keyword', 'value' => 'gambling']);

    expect(Stream::count())->toBe(1);
});

test('POST /blacklist validates required type and value', function () {
    $this->post('/blacklist', [])->assertSessionHasErrors(['type', 'value']);
});

test('DELETE /blacklist/{id} deletes the rule', function () {
    $rule = BlacklistRule::factory()->create();

    $this->delete("/blacklist/{$rule->id}")->assertRedirect();

    $this->assertDatabaseMissing('blacklist_rules', ['id' => $rule->id]);
});

test('POST /blacklist handles duplicate gracefully', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    BlacklistRule::factory()->channel('existing')->create();

    $this->post('/blacklist', ['type' => 'channel', 'value' => 'existing'])->assertRedirect();

    expect(BlacklistRule::where('value', 'existing')->count())->toBe(1);
});

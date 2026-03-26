<?php

use App\Models\Category;
use App\Models\Stream;
use App\Models\TrackedChannel;
use App\Services\SyncService;
use App\Services\TwitchApiService;

test('GET /tracking returns 200 with categories and channels', function () {
    Category::factory()->count(2)->create();
    TrackedChannel::factory()->create();

    $this->get('/tracking')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Tracking')
            ->has('categories', 2)
            ->has('channels', 1)
        );
});

test('GET /tracking sorts categories by name by default', function () {
    Category::factory()->create(['name' => 'Zelda']);
    Category::factory()->create(['name' => 'Alpha']);

    $this->get('/tracking')
        ->assertInertia(fn ($page) => $page
            ->where('categories.0.name', 'Alpha')
        );
});

test('GET /tracking/search searches Twitch categories', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('searchCategories')->andReturn([
            ['id' => '33214', 'name' => 'Fortnite', 'box_art_url' => 'https://example.com/art.jpg'],
        ]);
    });

    $this->getJson('/tracking/search?query=fortnite')
        ->assertOk()
        ->assertJsonCount(1);
});

test('GET /tracking/search returns error when Twitch not configured', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->getJson('/tracking/search?query=test')
        ->assertStatus(422);
});

test('POST /tracking/categories creates a new category', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->post('/tracking/categories', [
        'twitch_id' => '12345',
        'name' => 'Just Chatting',
        'box_art_url' => 'https://example.com/art.jpg',
    ])->assertRedirect();

    $this->assertDatabaseHas('categories', ['twitch_id' => '12345', 'name' => 'Just Chatting']);
});

test('POST /tracking/categories validates unique twitch_id', function () {
    Category::factory()->create(['twitch_id' => '12345']);

    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(false);
    });

    $this->from('/tracking')
        ->post('/tracking/categories', [
            'twitch_id' => '12345',
            'name' => 'Duplicate',
        ])->assertRedirect('/tracking')
        ->assertSessionHasErrors('twitch_id');
});

test('PUT /tracking/categories/{id} updates category settings', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->put("/tracking/categories/{$category->id}", [
        'is_active' => false,
        'min_viewers' => 50,
    ])->assertRedirect();

    $category->refresh();
    expect($category->is_active)->toBeFalse()
        ->and($category->min_viewers)->toBe(50);
});

test('DELETE /tracking/categories/{id} deletes category and streams', function () {
    $category = Category::factory()->create();
    Stream::factory()->forCategory($category)->count(3)->create();

    $this->delete("/tracking/categories/{$category->id}")->assertRedirect();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    expect(Stream::where('category_id', $category->id)->count())->toBe(0);
});

test('POST /tracking/categories/{id}/sync syncs category', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getAllStreamsForCategory')->andReturn([]);
        $mock->shouldReceive('getUsersByIds')->andReturn([]);
    });

    $category = Category::factory()->create();

    $this->post("/tracking/categories/{$category->id}/sync")->assertRedirect();
});

test('POST /tracking/channels creates a tracked channel', function () {
    $this->mock(TwitchApiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUsers')->andReturn([
            ['id' => '123', 'login' => 'shroud', 'display_name' => 'shroud', 'profile_image_url' => 'https://example.com/avatar.jpg'],
        ]);
        $mock->shouldReceive('getStreamsByUsers')->andReturn([]);
        $mock->shouldReceive('getUsersByIds')->andReturn([]);
    });

    $this->post('/tracking/channels', ['user_login' => 'shroud'])->assertRedirect();

    $this->assertDatabaseHas('tracked_channels', ['user_login' => 'shroud']);
});

test('POST /tracking/channels rejects duplicate', function () {
    TrackedChannel::factory()->create(['user_login' => 'shroud']);

    $this->post('/tracking/channels', ['user_login' => 'shroud'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('DELETE /tracking/channels/{id} deletes channel', function () {
    $channel = TrackedChannel::factory()->create();

    $this->delete("/tracking/channels/{$channel->id}")->assertRedirect();

    $this->assertDatabaseMissing('tracked_channels', ['id' => $channel->id]);
});

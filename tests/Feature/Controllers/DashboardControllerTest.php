<?php

use App\Models\Category;
use App\Models\Stream;

test('GET / returns 200 and renders Dashboard', function () {
    $this->get('/')->assertStatus(200);
});

test('shows streams from active categories', function () {
    $active = Category::factory()->create(['is_active' => true]);
    $stream = Stream::factory()->forCategory($active)->create(['user_name' => 'ActiveStreamer']);

    $this->get('/')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('streams')
        );
});

test('filters by language', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->create(['language' => 'en', 'user_login' => 'english']);
    Stream::factory()->forCategory($cat)->create(['language' => 'pl', 'user_login' => 'polish']);

    $this->get('/?lang=en')
        ->assertInertia(fn ($page) => $page
            ->has('streams', 1)
            ->where('streams.0.user_login', 'english')
        );
});

test('filters by min_viewers', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->withViewers(500)->create(['user_login' => 'big']);
    Stream::factory()->forCategory($cat)->withViewers(10)->create(['user_login' => 'small']);

    $this->get('/?min_viewers=100')
        ->assertInertia(fn ($page) => $page
            ->has('streams', 1)
            ->where('streams.0.viewer_count', 500)
        );
});

test('filters by hide_mature', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->create(['is_mature' => false, 'user_login' => 'clean']);
    Stream::factory()->forCategory($cat)->mature()->create(['user_login' => 'mature']);

    $this->get('/?hide_mature=1')
        ->assertInertia(fn ($page) => $page
            ->has('streams', 1)
            ->where('streams.0.user_login', 'clean')
        );
});

test('filters by search term matching user_name', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->create(['user_name' => 'TargetStreamer', 'user_login' => 'target']);
    Stream::factory()->forCategory($cat)->create(['user_name' => 'OtherPerson', 'user_login' => 'other']);

    $this->get('/?search=Target')
        ->assertInertia(fn ($page) => $page
            ->has('streams', 1)
            ->where('streams.0.user_login', 'target')
        );
});

test('sorts by viewers descending by default', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->withViewers(100)->create();
    Stream::factory()->forCategory($cat)->withViewers(500)->create();

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('streams.0.viewer_count', 500)
        );
});

test('sorts by viewers ascending', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->withViewers(500)->create();
    Stream::factory()->forCategory($cat)->withViewers(100)->create();

    $this->get('/?sort=viewers_asc')
        ->assertInertia(fn ($page) => $page
            ->where('streams.0.viewer_count', 100)
        );
});

test('sorts by name', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->create(['user_name' => 'Zelda']);
    Stream::factory()->forCategory($cat)->create(['user_name' => 'Alpha']);

    $this->get('/?sort=name')
        ->assertInertia(fn ($page) => $page
            ->where('streams.0.user_name', 'Alpha')
        );
});

test('returns available languages', function () {
    $cat = Category::factory()->create();
    Stream::factory()->forCategory($cat)->create(['language' => 'en']);
    Stream::factory()->forCategory($cat)->create(['language' => 'pl']);

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('languages')
        );
});

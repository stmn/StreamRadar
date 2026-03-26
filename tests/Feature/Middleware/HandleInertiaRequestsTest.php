<?php

use App\Models\AlertRule;
use App\Models\BlacklistRule;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Stream;

test('shares stats with correct counts', function () {
    $activeCategory = Category::factory()->create(['is_active' => true]);
    Category::factory()->inactive()->create();
    Stream::factory()->count(3)->forCategory($activeCategory)->create();
    AlertRule::factory()->count(2)->create(['is_active' => true]);
    AlertRule::factory()->inactive()->create();
    BlacklistRule::factory()->create();

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('stats.categories_count', 1)
            ->where('stats.streams_count', 3)
            ->where('stats.alerts_count', 2)
            ->where('stats.blacklist_count', 1)
        );
});

test('shares appSettings with theme', function () {
    Setting::set('theme', 'dark');

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('appSettings.theme', 'dark')
        );
});

test('shares appSettings with auth_enabled flag', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('pass'));

    $this->withSession(['authenticated' => true])
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('appSettings.auth_enabled', true)
        );
});

test('shares flash success message', function () {
    $this->withSession(['success' => 'It worked!'])
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('flash.success', 'It worked!')
        );
});

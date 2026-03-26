<?php

use App\Models\Setting;

test('seeds default settings', function () {
    $this->artisan('app:setup')->assertSuccessful();

    expect(Setting::get('theme'))->toBe('system')
        ->and(Setting::get('sync_frequency_minutes'))->toBe('5');
});

test('does not overwrite existing settings', function () {
    Setting::set('theme', 'dark');

    $this->artisan('app:setup')->assertSuccessful();

    expect(Setting::get('theme'))->toBe('dark');
});

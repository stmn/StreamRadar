<?php

use App\Models\Setting;

test('removes auth_username and auth_password settings', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('secret'));

    $this->artisan('auth:reset')->assertSuccessful();

    expect(Setting::get('auth_username'))->toBeNull()
        ->and(Setting::get('auth_password'))->toBeNull();
});

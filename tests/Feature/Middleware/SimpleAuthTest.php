<?php

use App\Models\Setting;

test('allows requests when no auth credentials configured', function () {
    $this->get('/')->assertStatus(200);
});

test('redirects to login when auth configured but not authenticated', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->get('/')->assertRedirect('/login');
});

test('allows requests when session is authenticated', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->withSession(['authenticated' => true])
        ->get('/')
        ->assertStatus(200);
});

test('allows GET /login even when not authenticated', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->get('/login')->assertStatus(200);
});

test('allows POST /login even when not authenticated', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'password',
    ])->assertRedirect('/');
});

test('redirects other routes to login when not authenticated', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->get('/settings')->assertRedirect('/login');
    $this->get('/alerts')->assertRedirect('/login');
    $this->get('/tracking')->assertRedirect('/login');
});

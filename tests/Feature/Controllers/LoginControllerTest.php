<?php

use App\Models\Setting;

test('GET /login renders login page', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->get('/login')->assertStatus(200);
});

test('POST /login with valid credentials authenticates', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'password',
    ])->assertRedirect('/');
});

test('POST /login with invalid credentials returns error', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'wrong',
    ])->assertRedirect()
        ->assertSessionHas('error');
});

test('POST /login validates required fields', function () {
    $this->post('/login', [])->assertSessionHasErrors(['username', 'password']);
});

test('POST /logout clears session and redirects', function () {
    Setting::set('auth_username', 'admin');
    Setting::set('auth_password', bcrypt('password'));

    $this->withSession(['authenticated' => true])
        ->post('/logout')
        ->assertRedirect('/login');
});

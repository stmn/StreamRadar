<?php

use App\Mail\StreamAlertMail;
use App\Models\AlertRule;
use App\Models\Stream;

test('subject mentions single stream name', function () {
    $stream = Stream::factory()->create(['user_name' => 'TestStreamer']);
    $rule = AlertRule::factory()->create();

    $mail = new StreamAlertMail([['rule' => $rule, 'stream' => $stream]]);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('TestStreamer');
});

test('subject mentions multiple streams', function () {
    $stream1 = Stream::factory()->create(['user_name' => 'Streamer1']);
    $stream2 = Stream::factory()->create(['user_name' => 'Streamer2']);
    $rule = AlertRule::factory()->create();

    $mail = new StreamAlertMail([
        ['rule' => $rule, 'stream' => $stream1],
        ['rule' => $rule, 'stream' => $stream2],
    ]);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('2');
});

test('HTML content includes stream user name', function () {
    $stream = Stream::factory()->create(['user_name' => 'CoolStreamer']);
    $rule = AlertRule::factory()->create();

    $mail = new StreamAlertMail([['rule' => $rule, 'stream' => $stream]]);
    $html = $mail->render();

    expect($html)->toContain('CoolStreamer');
});

test('HTML content includes Twitch URL', function () {
    $stream = Stream::factory()->create(['user_login' => 'coolstreamer']);
    $rule = AlertRule::factory()->create();

    $mail = new StreamAlertMail([['rule' => $rule, 'stream' => $stream]]);
    $html = $mail->render();

    expect($html)->toContain('https://www.twitch.tv/coolstreamer');
});

test('HTML content includes viewer count', function () {
    $stream = Stream::factory()->withViewers(12345)->create();
    $rule = AlertRule::factory()->create();

    $mail = new StreamAlertMail([['rule' => $rule, 'stream' => $stream]]);
    $html = $mail->render();

    expect($html)->toContain('12,345');
});

test('HTML content includes category name', function () {
    $stream = Stream::factory()->create(['game_name' => 'Fortnite']);
    $rule = AlertRule::factory()->create();

    $mail = new StreamAlertMail([['rule' => $rule, 'stream' => $stream]]);
    $html = $mail->render();

    expect($html)->toContain('Fortnite');
});

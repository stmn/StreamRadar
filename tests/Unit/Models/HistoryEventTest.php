<?php

use App\Models\HistoryEvent;

test('auto-sets created_at when not provided', function () {
    $event = HistoryEvent::factory()->create();

    expect($event->created_at)->not->toBeNull();
});

test('preserves provided created_at', function () {
    $date = now()->subDays(5);
    $event = HistoryEvent::factory()->create(['created_at' => $date]);

    expect($event->created_at->toDateString())->toBe($date->toDateString());
});

test('scopeRecent returns records ordered by created_at desc with limit', function () {
    HistoryEvent::factory()->create(['created_at' => now()->subMinutes(3)]);
    HistoryEvent::factory()->create(['created_at' => now()->subMinutes(1)]);
    HistoryEvent::factory()->create(['created_at' => now()->subMinutes(2)]);

    $recent = HistoryEvent::recent(2)->get();

    expect($recent)->toHaveCount(2)
        ->and($recent->first()->created_at->gt($recent->last()->created_at))->toBeTrue();
});

test('scopeOfType filters by type', function () {
    HistoryEvent::factory()->ofType('stream_online')->count(2)->create();
    HistoryEvent::factory()->ofType('alert_triggered')->create();

    expect(HistoryEvent::ofType('stream_online')->count())->toBe(2)
        ->and(HistoryEvent::ofType('alert_triggered')->count())->toBe(1);
});

test('casts metadata as array', function () {
    $event = HistoryEvent::factory()->create(['metadata' => ['key' => 'value']]);

    expect($event->metadata)->toBe(['key' => 'value']);
});

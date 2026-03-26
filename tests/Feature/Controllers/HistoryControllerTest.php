<?php

use App\Models\HistoryEvent;

test('GET /history returns 200', function () {
    $this->get('/history')->assertStatus(200);
});

test('GET /history excludes sync_completed events by default', function () {
    HistoryEvent::factory()->ofType('stream_online')->create();
    HistoryEvent::factory()->syncCompleted()->create();

    $this->get('/history')
        ->assertInertia(fn ($page) => $page
            ->component('History')
            ->has('events.data', 1)
        );
});

test('GET /history filters by type', function () {
    HistoryEvent::factory()->ofType('stream_online')->count(2)->create();
    HistoryEvent::factory()->ofType('alert_triggered')->create();

    $this->get('/history?type=stream_online')
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 2)
        );
});

test('GET /history filters by search term', function () {
    HistoryEvent::factory()->create(['streamer_name' => 'TargetStreamer']);
    HistoryEvent::factory()->create(['streamer_name' => 'OtherPerson']);

    $this->get('/history?search=Target')
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
        );
});

test('DELETE /history truncates all events', function () {
    HistoryEvent::factory()->count(5)->create();

    $this->delete('/history')->assertRedirect();

    expect(HistoryEvent::count())->toBe(0);
});

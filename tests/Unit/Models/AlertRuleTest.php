<?php

use App\Models\AlertRule;
use App\Models\Category;
use App\Models\Stream;
use App\Models\StreamAlertTracking;

// matchesStream()

test('matches any stream when rule has no criteria', function () {
    $rule = AlertRule::factory()->create();
    $stream = Stream::factory()->create();

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('rejects stream when streamer_login does not match', function () {
    $rule = AlertRule::factory()->forStreamer('targetuser')->create();
    $stream = Stream::factory()->create(['user_login' => 'otheruser']);

    expect($rule->matchesStream($stream))->toBeFalse();
});

test('matches stream when streamer_login matches case-insensitively', function () {
    $rule = AlertRule::factory()->forStreamer('TargetUser')->create();
    $stream = Stream::factory()->create(['user_login' => 'targetuser']);

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('rejects stream when category_id does not match', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    $rule = AlertRule::factory()->create(['category_id' => $category1->id]);
    $stream = Stream::factory()->create(['category_id' => $category2->id]);

    expect($rule->matchesStream($stream))->toBeFalse();
});

test('matches stream when category_id matches', function () {
    $category = Category::factory()->create();
    $rule = AlertRule::factory()->create(['category_id' => $category->id]);
    $stream = Stream::factory()->forCategory($category)->create();

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('rejects stream when viewer_count is below min_viewers', function () {
    $rule = AlertRule::factory()->create(['min_viewers' => 100]);
    $stream = Stream::factory()->withViewers(50)->create();

    expect($rule->matchesStream($stream))->toBeFalse();
});

test('matches stream when viewer_count meets min_viewers', function () {
    $rule = AlertRule::factory()->create(['min_viewers' => 100]);
    $stream = Stream::factory()->withViewers(100)->create();

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('matches stream when viewer_count exceeds min_viewers', function () {
    $rule = AlertRule::factory()->create(['min_viewers' => 100]);
    $stream = Stream::factory()->withViewers(500)->create();

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('rejects stream when language does not match', function () {
    $rule = AlertRule::factory()->create(['language' => 'en']);
    $stream = Stream::factory()->create(['language' => 'pl']);

    expect($rule->matchesStream($stream))->toBeFalse();
});

test('matches stream when language matches case-insensitively', function () {
    $rule = AlertRule::factory()->create(['language' => 'EN']);
    $stream = Stream::factory()->create(['language' => 'en']);

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('rejects stream when title contains none of the keywords', function () {
    $rule = AlertRule::factory()->create(['keywords' => ['ranked', 'competitive']]);
    $stream = Stream::factory()->create(['title' => 'Casual gaming with friends']);

    expect($rule->matchesStream($stream))->toBeFalse();
});

test('matches stream when title contains at least one keyword', function () {
    $rule = AlertRule::factory()->create(['keywords' => ['ranked', 'competitive']]);
    $stream = Stream::factory()->create(['title' => 'Playing ranked today!']);

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('keyword matching is case-insensitive', function () {
    $rule = AlertRule::factory()->create(['keywords' => ['RANKED']]);
    $stream = Stream::factory()->create(['title' => 'Playing ranked today!']);

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('combines all criteria and passes when all met', function () {
    $category = Category::factory()->create();
    $rule = AlertRule::factory()->create([
        'streamer_login' => 'mystreamer',
        'category_id' => $category->id,
        'min_viewers' => 50,
        'language' => 'en',
        'keywords' => ['ranked'],
    ]);
    $stream = Stream::factory()->forCategory($category)->create([
        'user_login' => 'mystreamer',
        'viewer_count' => 200,
        'language' => 'en',
        'title' => 'Ranked grind all day',
    ]);

    expect($rule->matchesStream($stream))->toBeTrue();
});

test('fails when one of multiple criteria is not met', function () {
    $category = Category::factory()->create();
    $rule = AlertRule::factory()->create([
        'streamer_login' => 'mystreamer',
        'category_id' => $category->id,
        'min_viewers' => 500,
    ]);
    $stream = Stream::factory()->forCategory($category)->create([
        'user_login' => 'mystreamer',
        'viewer_count' => 50,
    ]);

    expect($rule->matchesStream($stream))->toBeFalse();
});

// isForSpecificStreamer()

test('isForSpecificStreamer returns true when streamer_login is set', function () {
    $rule = AlertRule::factory()->forStreamer('someone')->create();

    expect($rule->isForSpecificStreamer())->toBeTrue();
});

test('isForSpecificStreamer returns false when streamer_login is null', function () {
    $rule = AlertRule::factory()->create();

    expect($rule->isForSpecificStreamer())->toBeFalse();
});

// Relationships

test('belongs to category', function () {
    $category = Category::factory()->create();
    $rule = AlertRule::factory()->create(['category_id' => $category->id]);

    expect($rule->category->id)->toBe($category->id);
});

test('has many trackings', function () {
    $rule = AlertRule::factory()->create();
    StreamAlertTracking::factory()->count(3)->create(['alert_rule_id' => $rule->id]);

    expect($rule->trackings)->toHaveCount(3);
});

test('latestTracking returns most recent triggered tracking', function () {
    $rule = AlertRule::factory()->create();
    StreamAlertTracking::factory()->create([
        'alert_rule_id' => $rule->id,
        'triggered_at' => now()->subHour(),
    ]);
    $latest = StreamAlertTracking::factory()->create([
        'alert_rule_id' => $rule->id,
        'triggered_at' => now(),
    ]);

    expect($rule->latestTracking->id)->toBe($latest->id);
});

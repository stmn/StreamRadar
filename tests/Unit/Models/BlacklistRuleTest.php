<?php

use App\Models\BlacklistRule;

test('scopeChannels filters by channel type', function () {
    BlacklistRule::factory()->channel()->count(2)->create();
    BlacklistRule::factory()->keyword()->create();

    expect(BlacklistRule::channels()->count())->toBe(2);
});

test('scopeKeywords filters by keyword type', function () {
    BlacklistRule::factory()->keyword()->count(3)->create();
    BlacklistRule::factory()->channel()->create();

    expect(BlacklistRule::keywords()->count())->toBe(3);
});

test('scopeTags filters by tag type', function () {
    BlacklistRule::factory()->tag()->count(2)->create();
    BlacklistRule::factory()->channel()->create();

    expect(BlacklistRule::tags()->count())->toBe(2);
});

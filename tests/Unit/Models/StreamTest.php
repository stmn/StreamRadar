<?php

use App\Models\Category;
use App\Models\Stream;

test('getThumbnailUrlSized replaces width and height placeholders', function () {
    $stream = Stream::factory()->create([
        'thumbnail_url' => 'https://example.com/thumb-{width}x{height}.jpg',
    ]);

    expect($stream->getThumbnailUrlSized(640, 360))->toBe('https://example.com/thumb-640x360.jpg');
});

test('getThumbnailUrlSized returns null when thumbnail_url is null', function () {
    $stream = Stream::factory()->create(['thumbnail_url' => null]);

    expect($stream->getThumbnailUrlSized())->toBeNull();
});

test('getThumbnailUrlSized uses default dimensions', function () {
    $stream = Stream::factory()->create([
        'thumbnail_url' => 'https://example.com/{width}x{height}.jpg',
    ]);

    expect($stream->getThumbnailUrlSized())->toBe('https://example.com/440x248.jpg');
});

test('getTwitchUrl returns correct url', function () {
    $stream = Stream::factory()->create(['user_login' => 'teststreamer']);

    expect($stream->getTwitchUrl())->toBe('https://www.twitch.tv/teststreamer');
});

test('scopeForActiveCategories returns streams with active categories', function () {
    $active = Category::factory()->create(['is_active' => true]);
    $inactive = Category::factory()->inactive()->create();

    Stream::factory()->forCategory($active)->create();
    Stream::factory()->forCategory($inactive)->create();

    expect(Stream::forActiveCategories()->count())->toBe(1);
});

test('scopeForActiveCategories excludes streams with no category', function () {
    Stream::factory()->create(['category_id' => null]);
    $active = Category::factory()->create(['is_active' => true]);
    Stream::factory()->forCategory($active)->create();

    expect(Stream::forActiveCategories()->count())->toBe(1);
});

test('belongs to category', function () {
    $category = Category::factory()->create();
    $stream = Stream::factory()->forCategory($category)->create();

    expect($stream->category->id)->toBe($category->id);
});

test('casts tags as array', function () {
    $stream = Stream::factory()->create(['tags' => ['English', 'FPS']]);

    expect($stream->tags)->toBe(['English', 'FPS']);
});

test('casts is_mature as boolean', function () {
    $stream = Stream::factory()->create(['is_mature' => true]);

    expect($stream->is_mature)->toBeTrue();
});

test('casts viewer_count as integer', function () {
    $stream = Stream::factory()->create(['viewer_count' => '1234']);

    expect($stream->viewer_count)->toBeInt()->toBe(1234);
});

<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Support\PexelsService;

beforeEach(function () {
    Storage::fake('public');
    config(['xms.pexels.api_key' => 'test-key']);
});

it('is disabled when no api key is configured', function () {
    config(['xms.pexels.api_key' => null]);

    expect(app(PexelsService::class)->enabled())->toBeFalse();
});

it('searches photos and maps the response to a flat shape', function () {
    Http::fake([
        'api.pexels.com/v1/search*' => Http::response([
            'photos' => [
                ['id' => 1, 'width' => 100, 'height' => 100, 'photographer' => 'Alice', 'url' => 'https://pexels.com/photo/1', 'src' => ['medium' => 'https://images.pexels.com/1-medium.jpg']],
            ],
        ]),
    ]);

    $result = app(PexelsService::class)->searchPhotos('cats', 1);

    expect($result['photos'])->toHaveCount(1)
        ->and($result['photos'][0]['id'])->toBe(1)
        ->and($result['photos'][0]['photographer'])->toBe('Alice');
});

it('uses the curated endpoint when the query is empty', function () {
    Http::fake(['api.pexels.com/v1/curated*' => Http::response(['photos' => []])]);

    app(PexelsService::class)->searchPhotos('', 1);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/curated'));
});

it('searches videos and maps the response to a flat shape, with a small preview file', function () {
    Http::fake([
        'api.pexels.com/videos/search*' => Http::response([
            'videos' => [
                [
                    'id' => 5, 'image' => 'https://images.pexels.com/5.jpg', 'duration' => 65,
                    'url' => 'https://pexels.com/video/5', 'user' => ['name' => 'Bob'],
                    'video_files' => [
                        ['file_type' => 'video/mp4', 'width' => 1920, 'link' => 'https://videos.pexels.com/5-hd.mp4'],
                        ['file_type' => 'video/mp4', 'width' => 640, 'link' => 'https://videos.pexels.com/5-sd.mp4'],
                    ],
                ],
            ],
        ]),
    ]);

    $result = app(PexelsService::class)->searchVideos('waves', 1);

    expect($result['videos'])->toHaveCount(1)
        ->and($result['videos'][0]['user'])->toBe('Bob')
        ->and($result['videos'][0]['duration'])->toBe(65)
        ->and($result['videos'][0]['preview_url'])->toBe('https://videos.pexels.com/5-sd.mp4');
});

it('downloads and stores a chosen photo as a pending upload', function () {
    Http::fake([
        'api.pexels.com/v1/photos/42' => Http::response([
            'photographer' => 'Alice', 'url' => 'https://pexels.com/photo/42', 'alt' => 'A cat',
            'src' => ['original' => 'https://images.pexels.com/42-original.jpg'],
        ]),
        'images.pexels.com/*' => Http::response(str_repeat('x', 100)),
    ]);

    $photo = app(PexelsService::class)->resolveAndStorePhoto(42);

    expect($photo)->not->toBeNull()
        ->and($photo['alt'])->toBe('A cat')
        ->and($photo['attribution'])->toBe('Photo by Alice on Pexels')
        ->and($photo['attribution_url'])->toBe('https://pexels.com/photo/42');

    Storage::disk('public')->assertExists($photo['path']);
});

it('rejects a photo download larger than the configured limit', function () {
    config(['xms.pexels.photo_max_bytes' => 10]);

    Http::fake([
        'api.pexels.com/v1/photos/42' => Http::response([
            'photographer' => 'Alice', 'url' => 'https://pexels.com/photo/42', 'alt' => '',
            'src' => ['original' => 'https://images.pexels.com/42-original.jpg'],
        ]),
        'images.pexels.com/*' => Http::response(str_repeat('x', 1000)),
    ]);

    expect(app(PexelsService::class)->resolveAndStorePhoto(42))->toBeNull();
});

it('downloads and stores a chosen video as a pending upload, picking the smallest suitable file', function () {
    Http::fake([
        'api.pexels.com/videos/videos/7' => Http::response([
            'url' => 'https://pexels.com/video/7',
            'user' => ['name' => 'Bob'],
            'video_files' => [
                ['file_type' => 'video/mp4', 'width' => 1920, 'link' => 'https://videos.pexels.com/7-hd.mp4'],
                ['file_type' => 'video/mp4', 'width' => 640, 'link' => 'https://videos.pexels.com/7-sd.mp4'],
            ],
        ]),
        'videos.pexels.com/*' => Http::response(str_repeat('x', 100)),
    ]);

    $video = app(PexelsService::class)->resolveAndStoreVideo(7);

    expect($video)->not->toBeNull()
        ->and($video['attribution'])->toBe('Video by Bob on Pexels');

    Storage::disk('public')->assertExists($video['path']);
    Http::assertSent(fn ($request) => $request->url() === 'https://videos.pexels.com/7-sd.mp4');
});

it('returns null when resolving an id of 0 or less', function () {
    expect(app(PexelsService::class)->resolveAndStorePhoto(0))->toBeNull()
        ->and(app(PexelsService::class)->resolveAndStoreVideo(-1))->toBeNull();
});

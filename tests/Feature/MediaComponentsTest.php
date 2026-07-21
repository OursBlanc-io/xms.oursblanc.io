<?php

use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Models\Page;

beforeEach(function () {
    Storage::fake('public');
});

it('renders a picture element with webp srcset for an attached image', function () {
    $page = Page::create(['locale' => 'fr', 'slug' => 'pic-1', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    $media = $page->addMedia(__DIR__.'/../Fixtures/test-image.png')
        ->preservingOriginal()
        ->toMediaCollection('block-u1');

    $html = (string) view('xms::components.picture', ['mediaId' => $media->id, 'alt' => 'A photo'])->render();

    expect($html)->toContain('<picture')
        ->toContain('type="image/webp"')
        ->toContain('w480')
        ->toContain('w960')
        ->toContain('w1920')
        ->toContain('alt="A photo"');
});

it('renders nothing for a picture component given no media id', function () {
    $html = trim((string) view('xms::components.picture', ['mediaId' => null])->render());

    expect($html)->toBe('');
});

it('renders a video element for an attached video', function () {
    $page = Page::create(['locale' => 'fr', 'slug' => 'vid-1', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    $media = $page->addMedia(__DIR__.'/../Fixtures/test-video.mp4')
        ->preservingOriginal()
        ->toMediaCollection('block-v1');

    $html = (string) view('xms::components.video', ['mediaId' => $media->id])->render();

    expect($html)->toContain('<video')
        ->toContain('type="video/mp4"');
});

it('renders an iframe embed for a video block given only an external url', function () {
    $html = (string) view('xms::components.video', ['url' => 'https://www.youtube.com/embed/xyz'])->render();

    expect($html)->toContain('<iframe')
        ->toContain('https://www.youtube.com/embed/xyz');
});
